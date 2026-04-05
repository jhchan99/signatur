<?php

return [

    'featured' => [
        'source' => 'open_library_curated',
        'list_name' => 'homepage_v1',
        'cache_ttl' => (int) env('BOOKS_FEATURED_CACHE_TTL', 3600),
        /*
        | Seed rows for homepage imports. Prefer `work_key` when stable; otherwise
        | title/author are resolved via Open Library search.
        */
        'seeds' => [
            ['title' => 'Harry Potter and the Prisoner of Azkaban (Harry Potter, #3)', 'author' => 'J.K. Rowling'],
            ['title' => 'Tomorrow, and Tomorrow, and Tomorrow', 'author' => 'Gabrielle Zevin'],
            ['title' => 'The Seven Husbands of Evelyn Hugo', 'author' => 'Taylor Jenkins Reid'],
            ['title' => 'The Night Circus', 'author' => 'Erin Morgenstern'],
            ['title' => 'Yellowface', 'author' => 'R. F. Kuang'],
            ['title' => 'Piranesi', 'author' => 'Susanna Clarke'],
        ],
    ],

];
