<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Beranda punya meta SEO lengkap; robots.txt & sitemap.xml tersedia.
 */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_seo_meta(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('<html lang="id">', false)
            ->assertSee('<title>'.config('app.name').' — ', false)
            ->assertSee('<meta name="description"', false)
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('name="twitter:card" content="summary_large_image"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('<meta name="csrf-token"', false);

        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
    }

    public function test_canonical_strips_tracking_parameters(): void
    {
        $this->get('/?utm_source=x&fbclid=y')
            ->assertSee('<link rel="canonical" href="'.url('/').'"', false);
    }

    public function test_non_production_is_noindex(): void
    {
        $this->get('/')
            ->assertSee('content="noindex, nofollow"', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_production_home_is_indexable_but_admin_is_not(): void
    {
        config(['seo.indexable' => true]);

        $this->get('/')->assertHeaderMissing('X-Robots-Tag');
        $this->get('/admin/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_robots_txt_and_sitemap_are_served(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Sitemap: '.url('sitemap.xml'));

        config(['seo.indexable' => true]);
        $this->get('/robots.txt')->assertSee('Disallow: /admin');

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>'.url('/').'</loc>', false)
            ->assertSee('<lastmod>', false);
    }
}
