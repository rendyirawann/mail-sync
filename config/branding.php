<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Warna brand
    |--------------------------------------------------------------------------
    |
    | Dipakai generator logo/favicon (php artisan app:branding) dan theme-color.
    | Warna utama harus lolos kontras WCAG AA untuk teks putih (>= 4.5:1).
    | Kosongkan untuk menurunkan warna otomatis dari nama aplikasi.
    |
    */

    'primary' => env('BRAND_PRIMARY_COLOR', '#1D4ED8'),
    'secondary' => env('BRAND_SECONDARY_COLOR', '#0E7490'),

    // Singkatan 1–3 huruf untuk ikon logo & favicon. Kosong = otomatis dari nama.
    'short' => env('BRAND_SHORT', 'MS'),

];
