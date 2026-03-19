<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Contact Deduplication & Matching
    |--------------------------------------------------------------------------
    */
    'deduplication' => [
        // Minimum score (0-100) to flag as a potential duplicate
        'score_threshold' => env('CONTACT_DEDUPE_THRESHOLD', 60),

        // Sensitivity for fuzzy name matching (0.0 to 1.0)
        'name_similarity_threshold' => env('CONTACT_NAME_SIMILARITY', 0.8),

        // Weighting factors for score calculation
        'weights' => [
            'phone' => 100,
            'name' => 50,
            'email' => 30,
            'tags' => 10,
        ],
    ],
];
