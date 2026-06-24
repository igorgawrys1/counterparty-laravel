<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Risk strategy
    |--------------------------------------------------------------------------
    | "rule_based" (default, deterministic, no AI) or "ai" (advisory, requires the
    | gawrys/counterparty-ai package). The AI strategy never decides hard pass/fail.
    */
    'strategy' => env('COUNTERPARTY_STRATEGY', 'rule_based'),

    'review_threshold' => 0.5,

    /*
    |--------------------------------------------------------------------------
    | Registries (per-country drivers), declared like filesystem "disks"
    |--------------------------------------------------------------------------
    | Toggle the capability-routed PL registry drivers. (The White List and VIES
    | checks are always wired and are not listed here.) Add your own via
    | Counterparty::extendRegistry().
    */
    'registries' => [
        'krs' => ['enabled' => true, 'base_uri' => null],
        'ceidg' => ['enabled' => false, 'token' => env('CEIDG_TOKEN')],
        'regon' => ['enabled' => false, 'token' => env('REGON_TOKEN')],
        'crbr' => ['enabled' => true, 'base_uri' => null],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sanctions screening
    |--------------------------------------------------------------------------
    | sanctions.network is the free default. OpenSanctions requires a commercial
    | licence; enable it deliberately.
    */
    'sanctions' => [
        // "sanctions_network" (free default) or "opensanctions".
        'provider' => env('COUNTERPARTY_SANCTIONS', 'sanctions_network'),
        'threshold' => 0.7,

        // OpenSanctions: commercial licence for the hosted API; or point base_uri at a
        // self-hosted (free) yente instance and leave api_key null.
        'opensanctions' => [
            'api_key' => env('OPENSANCTIONS_API_KEY'),
            'base_uri' => env('OPENSANCTIONS_BASE_URI', 'https://api.opensanctions.org'),
            'dataset' => env('OPENSANCTIONS_DATASET', 'sanctions'),
        ],
    ],

    'ai' => [
        'review_threshold' => 0.6,
        'cache_ttl' => 86400,
    ],
];
