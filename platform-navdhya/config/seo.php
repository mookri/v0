<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Programmatic SEO & Anti-Doorway Configuration
    |--------------------------------------------------------------------------
    |
    | Prevents doorway scaling, controls indexation cardinality, and enforces
    | canonical freshness and content uniqueness (Closes P-13, P-12).
    |
    */

    'cardinality_limits' => [
        // Daily panchang pages indexable only for validated top hubs
        'max_daily_places' => 50,

        // Number of forward days indexable for daily panchang (rest default to noindex)
        'max_future_days_indexable' => 30,

        // Number of past days indexable (rest default to noindex)
        'max_past_days_indexable' => 7,

        // Max yearly festival variations per festival
        'max_festival_years_indexable' => 3,
    ],

    'simhash' => [
        // Minimum Hamming distance between regional sibling pages (indicating < 85% duplicate content)
        'min_hamming_distance' => 3,
    ],

    'truthful_dates' => [
        // Require dateModified to be backed by actual content or data version hash changes
        'enforce_truthful_modified' => true,
    ],
];

