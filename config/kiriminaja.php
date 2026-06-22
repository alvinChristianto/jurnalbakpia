<?php

return [
    'api_key'             => env('KIRIMINAJA_API_KEY'),
    'base_url'            => env('KIRIMINAJA_BASE_URL', 'https://tdev.kiriminaja.com'),
    'sender_name'         => env('KIRIMINAJA_SENDER_NAME', 'Bakpia Master'),
    'sender_phone'        => env('KIRIMINAJA_SENDER_PHONE'),
    'sender_address'      => env('KIRIMINAJA_SENDER_ADDRESS'),
    'origin_kecamatan_id' => (int) env('KIRIMINAJA_ORIGIN_KECAMATAN_ID', 6983),
    'origin_kelurahan_id' => (int) env('KIRIMINAJA_ORIGIN_KELURAHAN_ID', 31409),
    // Box dimensions per 1 bakpia box — must match FE-bakpia/lib/package-dimensions.ts
    'box_weight_grams'    => 700,
    'box_length_cm'       => 14,
    'box_width_cm'        => 12,
    'box_height_cm'       => 5,
];
