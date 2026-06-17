<?php

return [
    'token' => env('FONNTE_TOKEN'),
    'base_url' => env('FONNTE_BASE_URL', 'https://api.fonnte.com'),
    'enabled' => (bool) env('FONNTE_ENABLED', true),
];
