<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use Throwable;

class MaritimeNewsService
{
    public const CACHE_KEY_PREFIX = 'maritime_news_feed_raw_';

    public const STALE_CACHE_KEY_PREFIX = 'maritime_news_feed_stale_';

    public const CACHE_TTL = 3600; // 1 hour

    /**
     * Get parsed freight news articles across sea, air, and road sources.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFreightNews(int $limit = 96): array
    {
        return $this->getMaritimeNews($limit);
    }

    /**
     * Resolve transport mode category (SEA, AIR, ROAD) for a given feed source.
     */
    public function resolveSourceMode(string $sourceName): string
    {
        $feedModes = config('cargotrace.news.feed_modes', []);
        if (isset($feedModes[$sourceName])) {
            return strtoupper((string) $feedModes[$sourceName]);
        }

        $lower = strtolower($sourceName);
        if (str_contains($lower, 'air') || str_contains($lower, 'cargo week')) {
            return 'AIR';
        }
        if (str_contains($lower, 'freightwaves') || str_contains($lower, 'transport topics') || str_contains($lower, 'carrier') || str_contains($lower, 'supply chain') || str_contains($lower, 'road') || str_contains($lower, 'truck')) {
            return 'ROAD';
        }

        return 'SEA';
    }

    /**
     * Get parsed multimodal freight news articles.
     * Concurrently fetches configured RSS/Atom feeds with fallback to stale cache.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMaritimeNews(int $limit = 96): array
    {
        $feedsConfig = config('cargotrace.news.feeds', [
            'gCaptain' => 'https://gcaptain.com/feed/',
            'Maritime Executive' => 'https://www.maritime-executive.com/articles.rss',
            'Splash247' => 'https://splash247.com/feed/',
            'World Cargo News' => 'https://www.worldcargonews.com/feed/',
            'Container News' => 'https://container-news.com/feed/',
            'Seatrade Maritime' => 'https://www.seatrade-maritime.com/rss.xml',
            'Hellenic Shipping News' => 'https://www.hellenicshippingnews.com/feed/',
            'Air Cargo News' => 'https://www.aircargonews.net/feed/',
            'Air Cargo Week' => 'https://aircargoweek.com/feed/',
            'FreightWaves' => 'https://www.freightwaves.com/feed',
            'Transport Topics' => 'https://www.ttnews.com/rss.xml',
            'Commercial Carrier Journal' => 'https://www.ccjdigital.com/rss',
            'Supply Chain Dive' => 'https://www.supplychaindive.com/feeds/news/',
        ]);

        $allArticles = [];
        $feedsToFetch = [];

        // Fetch optional GNews provider if key configured
        $gnewsArticles = $this->fetchGNewsArticles();
        foreach ($gnewsArticles as $item) {
            $allArticles[] = $item;
        }

        // Check per-feed fresh cache
        foreach ($feedsConfig as $name => $url) {
            $cacheKey = self::CACHE_KEY_PREFIX.md5($name);
            $cached = Cache::get($cacheKey);
            if ($cached && is_array($cached)) {
                foreach ($cached as $item) {
                    $item['data_source'] = 'cached';
                    $item['mode'] = $item['mode'] ?? $this->resolveSourceMode((string) ($item['source'] ?? $name));
                    $allArticles[] = $item;
                }
            } else {
                $feedsToFetch[$name] = $url;
            }
        }

        // Concurrently fetch feeds not in fresh cache
        if (! empty($feedsToFetch)) {
            $responses = [];
            try {
                $responses = Http::pool(function (Pool $pool) use ($feedsToFetch) {
                    foreach ($feedsToFetch as $name => $url) {
                        $pool->as($name)->timeout(6)->withHeaders([
                            'User-Agent' => 'CargoTrace/1.0 (Freight Visibility Platform; +http://cargotrace.test)',
                            'Accept' => 'application/rss+xml, application/atom+xml, application/xml, text/xml',
                        ])->get($url);
                    }
                });
            } catch (Throwable $e) {
                Log::warning('Concurrent RSS feeds fetch failed: '.$e->getMessage());
            }

            foreach ($feedsToFetch as $name => $url) {
                $cacheKey = self::CACHE_KEY_PREFIX.md5($name);
                $staleKey = self::STALE_CACHE_KEY_PREFIX.md5($name);

                $response = $responses[$name] ?? null;
                $feedArticles = [];

                if ($response instanceof Response && $response->successful() && ! empty($response->body())) {
                    $feedArticles = $this->parseRssFeed($response->body(), $name);
                }

                if (! empty($feedArticles)) {
                    Cache::put($cacheKey, $feedArticles, self::CACHE_TTL);
                    Cache::forever($staleKey, $feedArticles);
                    foreach ($feedArticles as $item) {
                        $item['data_source'] = 'live';
                        $allArticles[] = $item;
                    }
                } else {
                    // Tolerate per-feed failure: use stale cache if available
                    $stale = Cache::get($staleKey);
                    if ($stale && is_array($stale)) {
                        foreach ($stale as $item) {
                            $item['data_source'] = 'cached';
                            $item['mode'] = $item['mode'] ?? $this->resolveSourceMode((string) ($item['source'] ?? $name));
                            $allArticles[] = $item;
                        }
                    }
                }
            }
        }

        // De-duplicate articles by URL
        $deduped = [];
        foreach ($allArticles as $article) {
            $url = $article['url'] ?? '';
            if (! empty($url) && ! isset($deduped[$url])) {
                $deduped[$url] = $article;
            }
        }

        // Sort by published_at desc
        usort($deduped, function ($a, $b) {
            $ta = strtotime($a['published_at'] ?? '') ?: 0;
            $tb = strtotime($b['published_at'] ?? '') ?: 0;

            return $tb <=> $ta;
        });

        // Enrich thumbnails with og:image server-side (max 12 per refresh)
        $enriched = $this->enrichThumbnailsWithOg(array_values($deduped), 12);

        return array_slice($enriched, 0, $limit);
    }

    /**
     * Fetch articles from optional GNews provider if GNEWS_API_KEY is configured.
     * Caches result for 30 minutes. Silently skips if key missing or network fails.
     * Keys are never exposed to Blade or JS.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchGNewsArticles(): array
    {
        $key = config('services.gnews.key');
        if (empty($key)) {
            return [];
        }

        $cacheKey = 'maritime_gnews_articles_'.md5((string) $key);

        return Cache::remember($cacheKey, 1800, function () use ($key) {
            try {
                $response = Http::timeout(6)->withHeaders([
                    'User-Agent' => 'CargoTrace/1.0 (Freight Visibility Platform; +http://cargotrace.test)',
                ])->get('https://gnews.io/api/v4/search', [
                    'q' => '"air cargo" OR "ocean freight" OR "road freight" OR "container shipping"',
                    'lang' => 'en',
                    'max' => 10,
                    'apikey' => $key,
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $articles = [];
                    foreach (($json['articles'] ?? []) as $a) {
                        $cleanTitle = html_entity_decode(strip_tags($a['title'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $cleanSummary = html_entity_decode(strip_tags($a['description'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $cleanSummary = preg_replace('/\s+/', ' ', $cleanSummary);
                        if (mb_strlen($cleanSummary) > 200) {
                            $cleanSummary = mb_substr($cleanSummary, 0, 197).'...';
                        }
                        $articles[] = [
                            'headline' => $cleanTitle,
                            'title' => $cleanTitle,
                            'source' => $a['source']['name'] ?? 'GNews Freight',
                            'mode' => 'SEA',
                            'url' => $a['url'] ?? '',
                            'thumbnail' => $a['image'] ?? null,
                            'category' => 'Global Freight',
                            'summary' => $cleanSummary,
                            'published_at' => $a['publishedAt'] ?? Carbon::now()->toIso8601String(),
                            'data_source' => 'live',
                        ];
                    }

                    return $articles;
                }
            } catch (Throwable $e) {
                Log::info('GNews fetch skipped or failed: '.$e->getMessage());
            }

            return [];
        });
    }

    /**
     * Parse RSS XML payload into structured article records.
     * Cleans HTML entities and limits excerpts to 200 chars.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseRssFeed(string $xmlContent, string $sourceName): array
    {
        $articles = [];

        $mode = $this->resolveSourceMode($sourceName);

        try {
            $xml = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($xml === false) {
                return [];
            }

            if (isset($xml->channel->item)) {
                foreach ($xml->channel->item as $item) {
                    $rawTitle = trim((string) $item->title);
                    $link = trim((string) $item->link);
                    $pubDate = (string) $item->pubDate;
                    $category = isset($item->category) ? trim((string) $item->category) : 'Shipping & Trade';
                    $rawDescription = (string) $item->description;

                    // Content encoded check
                    $contentNs = $item->children('http://purl.org/rss/1.0/modules/content/');
                    if (isset($contentNs->encoded) && empty($rawDescription)) {
                        $rawDescription = (string) $contentNs->encoded;
                    }

                    // Decode HTML entities (e.g. &#8211;, &#8217;) and strip tags
                    $cleanTitle = html_entity_decode(strip_tags($rawTitle), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $cleanSummary = html_entity_decode(strip_tags($rawDescription), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $cleanSummary = preg_replace('/\s+/', ' ', $cleanSummary);
                    if (mb_strlen($cleanSummary) > 200) {
                        $cleanSummary = mb_substr($cleanSummary, 0, 197).'...';
                    }

                    $formattedDate = Carbon::now()->toIso8601String();
                    try {
                        if (! empty($pubDate)) {
                            $formattedDate = Carbon::parse($pubDate)->toIso8601String();
                        }
                    } catch (Throwable $e) {
                        // ignore date parsing exception
                    }

                    $thumbnail = $this->extractThumbnail($item);

                    $articles[] = [
                        'headline' => $cleanTitle,
                        'title' => $cleanTitle,
                        'source' => $sourceName,
                        'mode' => $mode,
                        'url' => $link,
                        'thumbnail' => $thumbnail,
                        'category' => $category,
                        'summary' => $cleanSummary,
                        'published_at' => $formattedDate,
                        'data_source' => 'live',
                    ];
                }
            } elseif (isset($xml->entry)) {
                foreach ($xml->entry as $entry) {
                    $rawTitle = trim((string) $entry->title);
                    $link = '';
                    if (isset($entry->link)) {
                        $link = (string) ($entry->link->attributes()['href'] ?? (string) $entry->link);
                    }
                    $pubDate = (string) ($entry->published ?? $entry->updated ?? '');
                    $category = 'Shipping & Trade';
                    if (isset($entry->category)) {
                        $category = trim((string) ($entry->category->attributes()['term'] ?? (string) $entry->category));
                    }
                    $rawDescription = (string) ($entry->summary ?? $entry->content ?? '');

                    $cleanTitle = html_entity_decode(strip_tags($rawTitle), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $cleanSummary = html_entity_decode(strip_tags($rawDescription), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $cleanSummary = preg_replace('/\s+/', ' ', $cleanSummary);
                    if (mb_strlen($cleanSummary) > 200) {
                        $cleanSummary = mb_substr($cleanSummary, 0, 197).'...';
                    }

                    $formattedDate = Carbon::now()->toIso8601String();
                    try {
                        if (! empty($pubDate)) {
                            $formattedDate = Carbon::parse($pubDate)->toIso8601String();
                        }
                    } catch (Throwable $e) {
                    }

                    $articles[] = [
                        'headline' => $cleanTitle,
                        'title' => $cleanTitle,
                        'source' => $sourceName,
                        'mode' => $mode,
                        'url' => $link,
                        'thumbnail' => null,
                        'category' => $category,
                        'summary' => $cleanSummary,
                        'published_at' => $formattedDate,
                        'data_source' => 'live',
                    ];
                }
            }
        } catch (Throwable $e) {
            Log::warning('Error parsing maritime RSS feed: '.$e->getMessage());
        }

        return $articles;
    }

    /**
     * Extract thumbnail image from RSS item according to priority order:
     * 1. media:content / media:thumbnail
     * 2. enclosure
     * 3. first <img> tag in content:encoded or description
     * 4. placeholder fallback
     */
    protected function extractThumbnail(SimpleXMLElement $item): ?string
    {
        // 1. media namespace (media:content or media:thumbnail)
        $mediaNs = $item->children('http://search.yahoo.com/mrss/');
        if (isset($mediaNs->content)) {
            $url = (string) ($mediaNs->content->attributes()['url'] ?? '');
            if (! empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                return $this->ensureHttps($url);
            }
        }
        if (isset($mediaNs->thumbnail)) {
            $url = (string) ($mediaNs->thumbnail->attributes()['url'] ?? '');
            if (! empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                return $this->ensureHttps($url);
            }
        }

        // 2. <enclosure url="..." type="image/...">
        if (isset($item->enclosure)) {
            $type = (string) ($item->enclosure->attributes()['type'] ?? '');
            $url = (string) ($item->enclosure->attributes()['url'] ?? '');
            if (! empty($url) && (empty($type) || str_starts_with($type, 'image/'))) {
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    return $this->ensureHttps($url);
                }
            }
        }

        // 3. first <img> tag inside content:encoded or description
        $contentNs = $item->children('http://purl.org/rss/1.0/modules/content/');
        if (isset($contentNs->encoded)) {
            $rawEncoded = (string) $contentNs->encoded;
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $rawEncoded, $matches)) {
                if (! empty($matches[1]) && filter_var($matches[1], FILTER_VALIDATE_URL)) {
                    return $this->ensureHttps($matches[1]);
                }
            }
        }

        $rawDescription = (string) $item->description;
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $rawDescription, $matches)) {
            if (! empty($matches[1]) && filter_var($matches[1], FILTER_VALIDATE_URL)) {
                return $this->ensureHttps($matches[1]);
            }
        }

        return null;
    }

    /**
     * Enrich articles with og:image fetched server-side from article webpage (max 12 per refresh).
     *
     * @param  array<int, array<string, mixed>>  $articles
     * @return array<int, array<string, mixed>>
     */
    protected function enrichThumbnailsWithOg(array $articles, int $max = 12): array
    {
        $count = 0;
        foreach ($articles as &$article) {
            if (! empty($article['thumbnail'])) {
                $article['thumbnail'] = $this->ensureHttps($article['thumbnail']);

                continue;
            }

            if ($count >= $max) {
                $article['thumbnail'] = $this->getCategoryPlaceholder($article['category'] ?? '');

                continue;
            }

            $count++;
            $url = $article['url'] ?? '';
            if (empty($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
                $article['thumbnail'] = $this->getCategoryPlaceholder($article['category'] ?? '');

                continue;
            }

            $cacheKey = 'og_image_'.md5($url);
            $ogImage = Cache::remember($cacheKey, 86400, function () use ($url) {
                try {
                    $res = Http::timeout(4)->withHeaders([
                        'User-Agent' => 'CargoTrace/1.0 (Maritime Visibility Platform; +http://cargotrace.test)',
                    ])->get($url);

                    if ($res->successful()) {
                        $html = $res->body();
                        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
                            return $matches[1];
                        }
                        if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i', $html, $matches)) {
                            return $matches[1];
                        }
                    }
                } catch (Throwable $e) {
                    Log::info("og:image fetch failed for {$url}: ".$e->getMessage());
                }

                return null;
            });

            if (! empty($ogImage) && filter_var($ogImage, FILTER_VALIDATE_URL)) {
                $article['thumbnail'] = $this->ensureHttps($ogImage);
            } else {
                $article['thumbnail'] = $this->getCategoryPlaceholder($article['category'] ?? '');
            }
        }
        unset($article);

        return $articles;
    }

    /**
     * Get intentional local placeholder based on category.
     */
    public function getCategoryPlaceholder(string $category): string
    {
        $cat = strtolower($category);
        if (str_contains($cat, 'port') || str_contains($cat, 'terminal') || str_contains($cat, 'harbor')) {
            return asset('images/hero-port.webp');
        }
        if (str_contains($cat, 'vessel') || str_contains($cat, 'ship') || str_contains($cat, 'sea') || str_contains($cat, 'ocean')) {
            return asset('images/hero-vessel.webp');
        }
        if (str_contains($cat, 'trade') || str_contains($cat, 'cargo') || str_contains($cat, 'logistics')) {
            return asset('images/hero-sunset-terminal.jpg');
        }

        return asset('images/news-placeholder.svg');
    }

    /**
     * Ensure image uses secure HTTPS protocol.
     */
    protected function ensureHttps(string $url): string
    {
        if (str_starts_with($url, 'http://')) {
            return 'https://'.substr($url, 7);
        }

        return $url;
    }

    /**
     * Mark articles as relevant to given shipments based on ports, airports (IATA),
     * cities, countries, carriers, and seeded spice/essential oil commodities.
     *
     * @param  array<int, array<string, mixed>>  $articles
     * @param  iterable|null  $shipments
     * @return array<int, array<string, mixed>>
     */
    public function tagShipmentRelevance(array $articles, $shipments = null): array
    {
        if (empty($articles) || empty($shipments)) {
            return $articles;
        }

        $commodityCatalog = [
            'nutmeg' => ['nutmeg'],
            'cloves' => ['cloves', 'clove'],
            'clove' => ['cloves', 'clove'],
            'cinnamon' => ['cinnamon', 'cassia'],
            'pepper' => ['pepper'],
            'ginger' => ['ginger'],
            'patchouli' => ['patchouli'],
        ];

        $keywords = [];
        $collectLocationTerms = static function ($location) use (&$keywords): void {
            if (! $location) {
                return;
            }
            $keywords[] = $location->unlocode;
            $keywords[] = $location->iata_code;
            $keywords[] = $location->display_code;
            $keywords[] = $location->city;
            $keywords[] = $location->country;

            $shortBase = trim(preg_replace('/\b(port|airport|depot|warehouse|border|cfs)\b/i', '', (string) ($location->short_name ?? '')));
            if (mb_strlen($shortBase) >= 3) {
                $keywords[] = $shortBase;
            }

            foreach (['Schiphol', 'Changi', 'Kualanamu', 'Belawan', 'Tanjung Priok', 'Nhava Sheva', 'Jebel Ali', 'Maasvlakte', 'Soekarno-Hatta', 'Charles de Gaulle'] as $hubName) {
                if (stripos((string) $location->name, $hubName) !== false) {
                    $keywords[] = $hubName;
                }
            }
        };

        foreach ($shipments as $s) {
            $collectLocationTerms($s->originLocation);
            $collectLocationTerms($s->destinationLocation);
            $collectLocationTerms($s->currentLocation);

            if ($s->carrier) {
                $keywords[] = $s->carrier->name;
            }

            $cargoText = (string) ($s->notes ?? '');
            if ($s->relationLoaded('items') || method_exists($s, 'items')) {
                foreach ($s->items as $item) {
                    $cargoText .= ' '.($item->description ?? '');
                }
            }

            foreach ($commodityCatalog as $needle => $aliases) {
                if (stripos($cargoText, $needle) !== false) {
                    foreach ($aliases as $alias) {
                        $keywords[] = $alias;
                    }
                }
            }
        }

        $cleanKeywords = array_values(array_unique(array_filter(array_map(fn ($k) => trim((string) $k), $keywords), function ($k) {
            return mb_strlen($k) >= 3;
        })));

        if (empty($cleanKeywords)) {
            return $articles;
        }

        foreach ($articles as &$article) {
            $text = ($article['headline'] ?? '').' '.($article['summary'] ?? '');
            $matches = [];
            foreach ($cleanKeywords as $kw) {
                if (preg_match('/\b'.preg_quote($kw, '/').'\b/i', $text)) {
                    $matches[] = $kw;
                }
            }

            $article['is_relevant'] = ! empty($matches);
            $article['relevant_terms'] = array_values(array_unique($matches));
        }
        unset($article);

        return $articles;
    }
}
