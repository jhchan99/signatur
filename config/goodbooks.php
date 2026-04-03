<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Goodbooks dataset directory
    |--------------------------------------------------------------------------
    |
    | Path to the unpacked goodbooks-10k-master folder (books.csv, tags.csv,
    | book_tags.csv). Override with GOODBOOKS_DATA_DIR in .env when needed.
    |
    */
    'data_dir' => env('GOODBOOKS_DATA_DIR', database_path('data/goodbooks-10k-master')),

    /*
    |--------------------------------------------------------------------------
    | Import filters
    |--------------------------------------------------------------------------
    */
    'allowed_language_codes' => ['eng', 'en-US', 'en-GB', 'en-CA'],

    'min_work_ratings_count' => (int) env('GOODBOOKS_MIN_WORK_RATINGS', 10_000),

    'max_subjects_per_work' => 40,

];
