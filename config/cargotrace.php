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
    'delay' => [
        'low_hours' => (float) env('CARGOTRACE_DELAY_LOW_HOURS', 6),
        'medium_hours' => (float) env('CARGOTRACE_DELAY_MEDIUM_HOURS', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | External API Integrations
    |--------------------------------------------------------------------------
    |
    | Configuration for OpenWeather, OpenStreetMap Nominatim,
    | Open Exchange Rates, and NewsAPI.
    |
    */
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
            'Maritime Executive' => 'https://maritime-executive.com/rss',
        ],
    ],
];
