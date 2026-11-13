<?php

use App\Models\Shipment;
use App\Models\User;
use App\Services\MaritimeNewsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Cache::flush();
});

test('maritime news service fetches feeds concurrently with Http pool and decodes html entities', function () {
    $gCaptainXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
  <channel>
    <title>gCaptain</title>
    <item>
      <title>Suez Canal Tolls Update &#8211; Carrier Fleet Guidelines</title>
      <link>https://gcaptain.com/suez-tolls-update-2026</link>
      <pubDate>Wed, 11 Nov 2026 12:00:00 +0000</pubDate>
      <category>Ocean Freight</category>
      <description><![CDATA[Container vessels&#8217; operational scheduling remains on schedule despite seasonal fog.]]></description>
    </item>
  </channel>
</rss>
XML;

    $maritimeExecXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>The Maritime Executive</title>
    <item>
      <title>Singapore Port Terminal Expands Automated Berths</title>
      <link>https://maritime-executive.com/article/singapore-berth-expansion</link>
      <pubDate>Wed, 11 Nov 2026 10:00:00 +0000</pubDate>
      <category>Port Operations</category>
      <description><![CDATA[PSA Singapore accelerates berth crane modernization to improve vessel turnaround.]]></description>
    </item>
  </channel>
</rss>
XML;

    Http::fake([
        'https://gcaptain.com/feed/' => Http::response($gCaptainXml, 200),
        'https://www.maritime-executive.com/articles.rss' => Http::response($maritimeExecXml, 200),
        'https://maritime-executive.com/rss' => Http::response($maritimeExecXml, 200),
        '*' => Http::response('', 200),
    ]);

    $service = app(MaritimeNewsService::class);
    $articles = $service->getMaritimeNews(10);

    expect($articles)->toHaveCount(2)
        ->and($articles[0]['headline'])->not->toContain('&#8211;')
        ->and($articles[0]['summary'])->not->toContain('&#8217;')
        ->and($articles[0]['data_source'])->toBe('live');

    // Both sources preserved
    $sources = collect($articles)->pluck('source')->all();
    expect($sources)->toContain('gCaptain')
        ->and($sources)->toContain('Maritime Executive');
});

test('maritime news service tolerates per-feed failure and falls back to stale cache if available', function () {
    $gCaptainXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>gCaptain</title>
    <item>
      <title>Rotterdam Terminal Container Throughput Surges</title>
      <link>https://gcaptain.com/rotterdam-throughput-2026</link>
      <pubDate>Wed, 11 Nov 2026 14:00:00 +0000</pubDate>
      <category>Port Operations</category>
      <description>Maasvlakte deepsea gates report record moves per hour.</description>
    </item>
  </channel>
</rss>
XML;

    // gCaptain succeeds, others return 500
    Http::fake([
        'https://gcaptain.com/feed/' => Http::response($gCaptainXml, 200),
        '*' => Http::response('', 500),
    ]);

    $service = app(MaritimeNewsService::class);
    $articles = $service->getMaritimeNews(10);

    expect($articles)->toHaveCount(1)
        ->and($articles[0]['headline'])->toBe('Rotterdam Terminal Container Throughput Surges');
});

test('maritime news service returns empty array without fabricated headlines when all feeds fail and no cache exists', function () {
    Http::fake([
        '*' => Http::failedConnection(),
    ]);

    $service = app(MaritimeNewsService::class);
    $articles = $service->getMaritimeNews(10);

    // No fabricated fallback headlines attributed to real companies
    expect($articles)->toBeEmpty();
});

test('maritime news service tags relevance matching active shipments', function () {
    $service = app(MaritimeNewsService::class);

    $articles = [
        [
            'headline' => 'Fog Advisory Issued for Singapore and Malacca Sea Lane',
            'summary' => 'Container liners passing SGSIN report reduced speeds.',
            'category' => 'Weather',
            'url' => 'https://example.com/fog-advisory',
        ],
        [
            'headline' => 'General Shipping Market Overview',
            'summary' => 'Baltic Dry Index shows modest upward momentum.',
            'category' => 'Markets',
            'url' => 'https://example.com/market-overview',
        ],
    ];

    $shipment = Shipment::whereHas('originLocation', fn ($q) => $q->where('unlocode', 'SGSIN'))->first()
        ?? Shipment::first();

    $tagged = $service->tagShipmentRelevance($articles, [$shipment]);

    expect($tagged[0]['is_relevant'])->toBeTrue()
        ->and($tagged[1]['is_relevant'])->toBeFalse();
});

test('news web hub renders paginated articles and handles empty search cleanly', function () {
    $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>gCaptain</title>
    <item>
      <title>Panama Canal Increases Daily Transit Slots</title>
      <link>https://gcaptain.com/panama-canal-slots-2026</link>
      <pubDate>Wed, 11 Nov 2026 12:00:00 +0000</pubDate>
      <category>Waterways</category>
      <description>Draft limits eased following sustained rainfall in Gatun Lake basin.</description>
    </item>
  </channel>
</rss>
XML;

    Http::fake([
        'https://gcaptain.com/feed/' => Http::response($xml, 200),
        '*' => Http::response('', 500),
    ]);

    $admin = User::where('role', 'admin')->first();

    $response = $this->actingAs($admin)->get(route('news.index'));
    $response->assertOk();
    $response->assertSee('Panama Canal Increases Daily Transit Slots');
    $response->assertSee('Waterways');

    // Filter by search query that returns no results
    $emptyResponse = $this->actingAs($admin)->get(route('news.index', ['q' => 'NON_EXISTENT_KEYWORD_XYZ']));
    $emptyResponse->assertOk();
    $emptyResponse->assertSee('No maritime news articles found');
});

test('shipment detail page renders related maritime news block', function () {
    $shipment = Shipment::first();
    $admin = User::where('role', 'admin')->first();

    $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>gCaptain</title>
    <item>
      <title>Terminal Crane Automation Update</title>
      <link>https://gcaptain.com/terminal-crane-update</link>
      <pubDate>Wed, 11 Nov 2026 12:00:00 +0000</pubDate>
      <category>Port Operations</category>
      <description>STS cranes automate container yard moves.</description>
    </item>
  </channel>
</rss>
XML;

    Http::fake([
        'https://gcaptain.com/feed/' => Http::response($xml, 200),
        '*' => Http::response('', 500),
    ]);

    $response = $this->actingAs($admin)->get(route('shipments.show', $shipment));
    $response->assertOk();
    $response->assertSee('Related Maritime News');
});

test('maritime news service handles empty RSS response without throwing', function () {
    Http::fake([
        '*' => Http::response('', 200),
    ]);

    $service = app(MaritimeNewsService::class);
    $articles = $service->getMaritimeNews(10);

    expect($articles)->toBeArray()
        ->and($articles)->toBeEmpty();
});

test('maritime news service fetches GNews articles when key configured', function () {
    config(['services.gnews.key' => 'TEST_GNEWS_KEY']);

    Http::fake([
        'https://gnews.io/api/v4/search*' => Http::response([
            'totalArticles' => 1,
            'articles' => [
                [
                    'title' => 'Global Container Port Throughput Rebounds',
                    'description' => 'Major hubs in Asia and Europe report increased vessel calls.',
                    'url' => 'https://example.com/gnews-port-rebound',
                    'publishedAt' => '2026-11-12T14:30:00Z',
                    'image' => 'https://example.com/port.jpg',
                    'source' => [
                        'name' => 'GNews Maritime',
                        'url' => 'https://example.com',
                    ],
                ],
            ],
        ], 200),
        '*' => Http::response('', 200),
    ]);

    $service = app(MaritimeNewsService::class);
    $articles = $service->getMaritimeNews(10);

    expect($articles)->not->toBeEmpty();
    $gnewsArticle = collect($articles)->firstWhere('source', 'GNews Maritime');
    expect($gnewsArticle)->not->toBeNull()
        ->and($gnewsArticle['headline'])->toBe('Global Container Port Throughput Rebounds')
        ->and($gnewsArticle['thumbnail'])->toBe('https://example.com/port.jpg');
});

test('maritime news service tolerates GNews timeout and empty response gracefully', function () {
    config(['services.gnews.key' => 'TEST_GNEWS_KEY']);

    // Timeout
    Http::fake([
        'https://gnews.io/api/v4/search*' => fn () => throw new ConnectionException('Connection timed out'),
        '*' => Http::response('', 200),
    ]);

    $service = app(MaritimeNewsService::class);
    $articles = $service->getMaritimeNews(10);
    expect($articles)->toBeArray();

    // Empty response
    Cache::flush();
    Http::fake([
        'https://gnews.io/api/v4/search*' => Http::response(['articles' => []], 200),
        '*' => Http::response('', 200),
    ]);

    $articlesEmpty = $service->getMaritimeNews(10);
    expect($articlesEmpty)->toBeArray();
});

test('maritime news service skips GNews provider silently when key is missing', function () {
    config(['services.gnews.key' => null]);

    Http::fake([
        '*' => Http::response('', 200),
    ]);

    $service = app(MaritimeNewsService::class);
    $articles = $service->getMaritimeNews(10);

    expect($articles)->toBeArray()
        ->and($articles)->toBeEmpty();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'gnews.io'));
});
