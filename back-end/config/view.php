<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | The project keeps its presentation layer in the sibling front-end/ folder,
    | alongside back-end/ and database/. resources/views stays in the list as a
    | fallback so package-published views still resolve.
    |
    */

    'paths' => [
        base_path('../front-end/views'),
        resource_path('views'),
    ],

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),

];
