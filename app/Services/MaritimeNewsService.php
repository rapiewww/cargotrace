<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use Throwable;

class MaritimeNewsService
{
    /**
     * Cache key for maritime news feed
     */
    public const CACHE_KEY = 'maritime_news_feed_articles';

    /**
     * Cache TTL in seconds (1 hour)
     */
    protected int $cacheTtl = 3600;

    /**
     * Primary trusted maritime RSS feed URLs
     *
     * @var array<string, string>
     */
    protected array $rssFeeds = [
        'gCaptain' => 'https://gcaptain.com/feed/',
        'Maritime Executive' => 'https://maritime-executive.com/rss',
    ];

    /**
     * Get parsed maritime shipping news articles.
     *
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    public function getMaritimeNews(int $limit = 6): array
    {
        if (Cache::has(self::CACHE_KEY)) {
            $cached = Cache::get(self::CACHE_KEY);
            if (is_array($cached) && ! empty($cached)) {
                return array_map(function ($item) {
                    $item['data_source'] = 'cached';
                    return $item;
                }, array_slice($cached, 0, $limit));
            }
        }

        // Attempt live fetch from RSS feed
        foreach ($this->rssFeeds as $sourceName => $feedUrl) {
            try {
                $response = Http::timeout(6)->withHeaders([
                    'User-Agent' => 'CargoTrace/1.0 (Maritime Visibility Platform; +http://cargotrace.test)',
                    'Accept' => 'application/rss+xml, application/xml, text/xml',
                ])->get($feedUrl);

                if ($response->successful() && ! empty($response->body())) {
                    $articles = $this->parseRssFeed($response->body(), $sourceName);
                    if (! empty($articles)) {
                        Cache::put(self::CACHE_KEY, $articles, $this->cacheTtl);

                        return array_map(function ($item) {
                            $item['data_source'] = 'live';
                            return $item;
                        }, array_slice($articles, 0, $limit));
                    }
                }
            } catch (Throwable $e) {
                Log::warning("Maritime RSS feed fetch failed for {$sourceName}: " . $e->getMessage());
            }
        }

        // Return deterministic fallback maritime news if live feeds fail
        return $this->getFallbackNews($limit);
    }

    /**
     * Parse RSS XML payload into structured article records.
     *
     * @param string $xmlContent
     * @param string $sourceName
     * @return array<int, array<string, mixed>>
     */
    protected function parseRssFeed(string $xmlContent, string $sourceName): array
    {
        $articles = [];

        try {
            // Disable libxml entity loader issues and enable CDATA parsing
            $xml = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($xml === false || ! isset($xml->channel->item)) {
                return [];
            }

            foreach ($xml->channel->item as $item) {
                $title = trim((string) $item->title);
                $link = trim((string) $item->link);
                $pubDate = (string) $item->pubDate;
                $category = isset($item->category) ? trim((string) $item->category) : 'Shipping & Trade';
                $description = strip_tags(trim((string) $item->description));

                // Clean and truncate summary
                $cleanSummary = preg_replace('/\s+/', ' ', $description);
                if (strlen($cleanSummary) > 200) {
                    $cleanSummary = substr($cleanSummary, 0, 197) . '...';
                }

                $formattedDate = Carbon::now()->toIso8601String();
                try {
                    if (! empty($pubDate)) {
                        $formattedDate = Carbon::parse($pubDate)->toIso8601String();
                    }
                } catch (Throwable $e) {
                    // ignore parse exception
                }

                $thumbnail = $this->extractThumbnail($item);

                $articles[] = [
                    'headline' => $title,
                    'title' => $title,
                    'source' => $sourceName,
                    'url' => $link,
                    'thumbnail' => $thumbnail,
                    'category' => $category,
                    'summary' => $cleanSummary,
                    'published_at' => $formattedDate,
                    'data_source' => 'live',
                ];
            }
        } catch (Throwable $e) {
            Log::warning('Error parsing maritime RSS feed: ' . $e->getMessage());
        }

        return $articles;
    }

    /**
     * Extract thumbnail image from RSS item.
     *
     * @param SimpleXMLElement $item
     * @return string
     */
    protected function extractThumbnail(SimpleXMLElement $item): string
    {
        $defaultPlaceholder = asset('images/news-placeholder.svg');

        // 1. Check media namespace (media:content or media:thumbnail)
        $mediaNs = $item->children('http://search.yahoo.com/mrss/');
        if (isset($mediaNs->content)) {
            $url = (string) ($mediaNs->content->attributes()['url'] ?? '');
            if (! empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
        }
        if (isset($mediaNs->thumbnail)) {
            $url = (string) ($mediaNs->thumbnail->attributes()['url'] ?? '');
            if (! empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
        }

        // 2. Check <enclosure url="..." type="image/...">
        if (isset($item->enclosure)) {
            $type = (string) ($item->enclosure->attributes()['type'] ?? '');
            $url = (string) ($item->enclosure->attributes()['url'] ?? '');
            if (! empty($url) && (empty($type) || str_starts_with($type, 'image/'))) {
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    return $url;
                }
            }
        }

        // 3. Check <img> tag inside description or content:encoded
        $rawDescription = (string) $item->description;
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $rawDescription, $matches)) {
            if (! empty($matches[1]) && filter_var($matches[1], FILTER_VALIDATE_URL)) {
                return $matches[1];
            }
        }

        $contentNs = $item->children('http://purl.org/rss/1.0/modules/content/');
        if (isset($contentNs->encoded)) {
            $rawEncoded = (string) $contentNs->encoded;
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $rawEncoded, $matches)) {
                if (! empty($matches[1]) && filter_var($matches[1], FILTER_VALIDATE_URL)) {
                    return $matches[1];
                }
            }
        }

        return $defaultPlaceholder;
    }

    /**
     * Deterministic fallback maritime news bulletins.
     *
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    public function getFallbackNews(int $limit = 6): array
    {
        $items = [
            [
                'headline' => 'San Pedro Bay Ports Automate Container Gate Flow to Minimize Bottlenecks',
                'title' => 'San Pedro Bay Ports Automate Container Gate Flow to Minimize Bottlenecks',
                'source' => 'Journal of Commerce',
                'url' => 'https://www.joc.com/maritime-news',
                'thumbnail' => '/images/hero-sunset-terminal.jpg',
                'category' => 'Port Operations',
                'summary' => 'Terminals in Los Angeles and Long Beach implement unified digital appointment systems, reducing truck queue times by 28%.',
                'published_at' => Carbon::now()->subHours(2)->toIso8601String(),
                'data_source' => 'simulated',
            ],
            [
                'headline' => 'North Sea Storm Front: Quay Crane Operations Suspended at Key Terminals',
                'title' => 'North Sea Storm Front: Quay Crane Operations Suspended at Key Terminals',
                'source' => 'Port of Rotterdam Authority',
                'url' => 'https://www.portofrotterdam.com/en/news',
                'thumbnail' => '/images/hero-vessel.webp',
                'category' => 'Weather Advisory',
                'summary' => 'Severe seasonal storm gusts exceeding 40 knots prompt port safety authorities to enact crane speed throttles.',
                'published_at' => Carbon::now()->subHours(5)->toIso8601String(),
                'data_source' => 'simulated',
            ],
            [
                'headline' => 'Suez and Malacca Transit Corridors Report Elevated Vessel Flow Rates',
                'title' => 'Suez and Malacca Transit Corridors Report Elevated Vessel Flow Rates',
                'source' => "Lloyd's List Maritime Intelligence",
                'url' => 'https://www.lloydslist.com',
                'thumbnail' => '/images/hero-port.webp',
                'category' => 'Ocean Freight',
                'summary' => 'Container shipping capacity remains stable as carriers optimize voyage speeds and bunker fuel efficiency on major East-West loops.',
                'published_at' => Carbon::now()->subHours(8)->toIso8601String(),
                'data_source' => 'simulated',
            ],
            [
                'headline' => 'IMO Reaffirms Carbon Intensity Indicator (CII) Rating Enforcement Guidelines',
                'title' => 'IMO Reaffirms Carbon Intensity Indicator (CII) Rating Enforcement Guidelines',
                'source' => 'IMO Maritime Wire',
                'url' => 'https://www.imo.org',
                'thumbnail' => '/images/news-placeholder.svg',
                'category' => 'Regulations',
                'summary' => 'International Maritime Organization outlines verification frameworks for container liner decarbonization compliance.',
                'published_at' => Carbon::now()->subDay()->toIso8601String(),
                'data_source' => 'simulated',
            ],
            [
                'headline' => 'Digital Berth Allocation System Cuts Vessel Anchorage Waiting Time by 35%',
                'title' => 'Digital Berth Allocation System Cuts Vessel Anchorage Waiting Time by 35%',
                'source' => 'Maritime Executive',
                'url' => 'https://maritime-executive.com',
                'thumbnail' => '/images/hero-port.webp',
                'category' => 'Port Operations',
                'summary' => 'Major transshipment hubs deploy predictive berth allocation, smoothing container throughput and queue coordination.',
                'published_at' => Carbon::now()->subDays(2)->toIso8601String(),
                'data_source' => 'simulated',
            ],
            [
                'headline' => 'Transpacific Spot Container Freight Index Stabilizes Ahead of Peak Inflow',
                'title' => 'Transpacific Spot Container Freight Index Stabilizes Ahead of Peak Inflow',
                'source' => 'gCaptain',
                'url' => 'https://gcaptain.com',
                'thumbnail' => '/images/hero-sunset-terminal.jpg',
                'category' => 'Ocean Freight',
                'summary' => 'Container spot freight rates along Asia-US West Coast corridors stabilize as carriers adjust blank sailings.',
                'published_at' => Carbon::now()->subDays(3)->toIso8601String(),
                'data_source' => 'simulated',
            ],
        ];

        return array_slice($items, 0, $limit);
    }
}

