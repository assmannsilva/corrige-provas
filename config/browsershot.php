<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Node & Chromium binaries
    |--------------------------------------------------------------------------
    |
    | Browsershot drives Puppeteer through Node.js and a Chromium executable.
    | These paths are auto-detected by default; override them when the binaries
    | live in non-standard locations (e.g. inside Docker containers).
    |
    */

    'node_binary' => env('BROWSERSHOT_NODE_BINARY'),

    'npm_binary' => env('BROWSERSHOT_NPM_BINARY'),

    'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),

];
