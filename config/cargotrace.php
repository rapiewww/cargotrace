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
    | Dark theme uses bg-dark.webp by default. Light theme uses hero-light.webp
    | if present on disk or specified here; otherwise falls back to a clean
    | pale theme gradient with the SVG network motif.
    |
    */
    'hero_images' => [
        'light' => env('CARGOTRACE_HERO_LIGHT_IMAGE', null),
    ],

    'delay' => [
        'low_hours' => (float) env('CARGOTRACE_DELAY_LOW_HOURS', 6),
        'medium_hours' => (float) env('CARGOTRACE_DELAY_MEDIUM_HOURS', 24),
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
        ],
    ],
];
