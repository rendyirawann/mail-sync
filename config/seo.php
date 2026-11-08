<?php

/*
|--------------------------------------------------------------------------
| SEO
|--------------------------------------------------------------------------
|
| Nilai default meta SEO. Dibaca oleh partial resources/views/partials/seo.blade.php
| dan bisa ditimpa per halaman lewat @section('title'), @section('meta_description'),
| @section('og_image'). Nilai dari menu Pengaturan (tabel settings) tetap diprioritaskan
| oleh App\Support\Brand bila diisi admin.
|
*/

return [

    'site_name' => env('APP_NAME', 'Mail Sync'),

    'tagline' => env('SEO_TAGLINE', 'Sinkronisasi email dalam satu panel'),

    'description' => env('APP_DESCRIPTION', 'Aplikasi web untuk menyinkronkan, mengelola, dan memantau email antar akun dalam satu panel admin.'),

    'keywords' => env('SEO_KEYWORDS', 'mail sync, sinkronisasi email, email, imap, manajemen email, panel admin'),

    // Path relatif public/ atau URL absolut.
    'og_image' => env('SEO_OG_IMAGE') ?: 'og-image.png',

    'locale' => 'id_ID',

    // Selain production, seluruh situs diberi noindex agar staging/local tidak terindeks.
    'indexable' => env('APP_ENV') === 'production',

    // Prefix path yang selalu noindex (auth, panel admin).
    'noindex_paths' => ['admin', 'admin/*', 'login', 'register', 'password/*'],

    // Halaman publik yang masuk sitemap.xml (nama route).
    'sitemap_routes' => ['home'],

    // Query string pelacak yang dibuang dari URL canonical.
    'strip_query' => ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid'],

];
