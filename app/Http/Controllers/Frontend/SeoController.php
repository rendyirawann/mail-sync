<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * robots.txt dan sitemap.xml dinamis.
 *
 * Dinamis (bukan file statis) agar URL sitemap mengikuti APP_URL dan seluruh
 * situs otomatis tertutup bagi crawler saat APP_ENV bukan production.
 */
class SeoController extends Controller
{
    /** Path privat yang tidak boleh dirayapi. Sesuaikan bila menambah area privat. */
    private const DISALLOW = [
        '/admin',
        '/dashboard',
        '/login',
        '/register',
        '/password',
        '/api',
        '/broadcasting',
        '/storage',
        '/up',
    ];

    /**
     * Isi robots.txt.
     */
    public function robots(): Response
    {
        $lines = ['User-agent: *'];

        if (! config('seo.indexable')) {
            // Local/staging: tutup seluruh situs.
            $lines[] = 'Disallow: /';
        } else {
            $lines[] = 'Allow: /';
            foreach (self::DISALLOW as $path) {
                $lines[] = 'Disallow: ' . $path;
            }
        }

        $lines[] = '';
        $lines[] = 'Sitemap: ' . url('sitemap.xml');

        return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * sitemap.xml berisi halaman publik GET tanpa auth (config('seo.sitemap_routes')).
     * Dicache 1 jam; kosongkan dengan `php artisan cache:clear`.
     */
    public function sitemap(): Response
    {
        $xml = Cache::remember('seo.sitemap.' . md5((string) config('app.url')), 3600, function (): string {
            $lastmod = now()->toAtomString();
            $urls = '';

            foreach ((array) config('seo.sitemap_routes', []) as $name) {
                if (! Route::has($name)) {
                    continue;
                }

                $loc = htmlspecialchars(route($name), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $urls .= "  <url>\n    <loc>{$loc}</loc>\n    <lastmod>{$lastmod}</lastmod>\n    <changefreq>weekly</changefreq>\n    <priority>1.0</priority>\n  </url>\n";
            }

            return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
                . $urls
                . '</urlset>' . "\n";
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
