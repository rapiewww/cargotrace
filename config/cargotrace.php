<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Delay Evaluation Thresholds
    |--------------------------------------------------------------------------
    |
    | Define delay thresholds in hours to categorize delay severity:
    | <= 0 hours: NONE
    | > 0 to 6 hours: LOW
    | > 6 to 24 hours: MEDIUM
    | > 24 hours: HIGH
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Hero Imagery Configuration
    |--------------------------------------------------------------------------
    |
    | Dark theme defaults to hero-multimodal-dark.jpg (falling back to
    | bg-dark.webp). Light theme defaults to hero-multimodal-light.jpg
    | (falling back to hero-light.webp, bg-light.webp, or a clean pale
    | gradient with the SVG network motif).
    |
    */
    'hero_images' => [
        'dark' => env('CARGOTRACE_HERO_DARK_IMAGE', null),
        'light' => env('CARGOTRACE_HERO_LIGHT_IMAGE', null),
    ],

    'delay' => [
        'low_hours' => (float) env('CARGOTRACE_DELAY_LOW_HOURS', 12),
        'medium_hours' => (float) env('CARGOTRACE_DELAY_MEDIUM_HOURS', 48),
        'air' => [
            'low_hours' => (float) env('CARGOTRACE_DELAY_AIR_LOW_HOURS', 3),
            'medium_hours' => (float) env('CARGOTRACE_DELAY_AIR_MEDIUM_HOURS', 12),
        ],
        'road' => [
            'low_hours' => (float) env('CARGOTRACE_DELAY_ROAD_LOW_HOURS', 6),
            'medium_hours' => (float) env('CARGOTRACE_DELAY_ROAD_MEDIUM_HOURS', 24),
        ],
        'sea' => [
            'low_hours' => (float) env('CARGOTRACE_DELAY_SEA_LOW_HOURS', 12),
            'medium_hours' => (float) env('CARGOTRACE_DELAY_SEA_MEDIUM_HOURS', 48),
        ],
    ],

    'free_time' => [
        'days' => (int) env('CARGOTRACE_FREE_DAYS_AFTER_ARRIVAL', 5),
    ],

    'notifications' => [
        'email' => (bool) env('CARGOTRACE_NOTIFICATIONS_EMAIL', false),
    ],

    'weather' => [
        'crane_thresholds' => [
            'caution_kph' => (float) env('CARGOTRACE_WEATHER_CRANE_CAUTION_KPH', 28.0),
            'suspended_kph' => (float) env('CARGOTRACE_WEATHER_CRANE_SUSPENDED_KPH', 40.0),
        ],
        'beaufort_thresholds' => [
            ['force' => 1, 'max_kph' => 5.9, 'desc' => 'Light Air', 'color' => 'emerald'],
            ['force' => 2, 'max_kph' => 11.9, 'desc' => 'Light Breeze', 'color' => 'emerald'],
            ['force' => 3, 'max_kph' => 19.9, 'desc' => 'Gentle Breeze', 'color' => 'emerald'],
            ['force' => 4, 'max_kph' => 28.9, 'desc' => 'Moderate Breeze', 'color' => 'cyan'],
            ['force' => 5, 'max_kph' => 38.9, 'desc' => 'Fresh Breeze', 'color' => 'amber'],
            ['force' => 6, 'max_kph' => 49.9, 'desc' => 'Strong Breeze', 'color' => 'orange'],
            ['force' => 7, 'max_kph' => PHP_FLOAT_MAX, 'desc' => 'Near Gale / Heavy Seas', 'color' => 'rose'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | External API Integrations
    |--------------------------------------------------------------------------
    |
    | Configuration for OpenWeather, OpenStreetMap Nominatim,
    | Open Exchange Rates, NewsAPI, and Map Tile Providers.
    |
    */
    'map' => [
        'tile_url' => env('CARGOTRACE_MAP_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'attribution' => env('CARGOTRACE_MAP_TILE_ATTRIBUTION', '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'),
    ],

    'openweather' => [
        'key' => env('OPENWEATHER_API_KEY'),
        'base_url' => env('OPENWEATHER_BASE_URL', 'https://api.openweathermap.org/data/2.5'),
    ],

    'nominatim' => [
        'base_url' => env('NOMINATIM_BASE_URL', 'https://nominatim.openstreetmap.org'),
    ],

    'exchangerate' => [
        'key' => env('EXCHANGERATE_API_KEY'),
        'base_url' => env('EXCHANGERATE_BASE_URL', 'https://open.er-api.com/v6/latest'),
    ],

    'news' => [
        'key' => env('NEWS_API_KEY'),
        'base_url' => env('NEWS_API_BASE_URL', 'https://newsapi.org/v2'),
        'feeds' => [
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
        ],
        'feed_modes' => [
            'gCaptain' => 'SEA',
            'Maritime Executive' => 'SEA',
            'The Maritime Executive' => 'SEA',
            'Splash247' => 'SEA',
            'World Cargo News' => 'SEA',
            'Container News' => 'SEA',
            'Seatrade Maritime' => 'SEA',
            'Hellenic Shipping News' => 'SEA',
            'Air Cargo News' => 'AIR',
            'Air Cargo Week' => 'AIR',
            'FreightWaves' => 'ROAD',
            'Transport Topics' => 'ROAD',
            'Commercial Carrier Journal' => 'ROAD',
            'Supply Chain Dive' => 'ROAD',
        ],
    ],
];
