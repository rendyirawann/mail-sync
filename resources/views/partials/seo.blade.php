{{--
    Blok <head> untuk SEO, social card, favicon, dan CSRF. Di-include di SEMUA layout.

    Default dibaca dari config/seo.php + Pengaturan (App\Support\Brand, $brand).
    Override per halaman:
      @section('title', 'Judul Halaman')          → "Judul Halaman | Mail Sync"
      @section('meta_description', '...')          → maks ±155 karakter
      @section('og_image', asset('img/x.png'))
      @section('robots', 'noindex, nofollow')      → paksa noindex
    Beranda: @section('is_home', '1') → "Mail Sync — tagline".
--}}
@php
    $siteName = $brand['name'] ?? config('app.name');
    $pageTitle = trim($__env->yieldContent('title'));
    $isHome = trim($__env->yieldContent('is_home')) !== '';
    $fullTitle = ($isHome || $pageTitle === '')
        ? $siteName . ' — ' . ($brand['tagline'] ?? config('seo.tagline'))
        : $pageTitle . ' | ' . $siteName;
    $metaDescription = \Illuminate\Support\Str::limit(
        trim($__env->yieldContent('meta_description')) ?: ($brand['description'] ?? config('seo.description')),
        155,
        '…'
    );
    $ogImage = trim($__env->yieldContent('og_image')) ?: ($brand['og_image_url'] ?? asset('og-image.png'));

    // Canonical tanpa query string pelacak (utm_*, fbclid, gclid).
    $query = \Illuminate\Support\Arr::except(request()->query(), (array) config('seo.strip_query', []));
    $canonical = url()->current() . ($query ? '?' . http_build_query($query) : '');

    // Selain production, atau halaman admin/auth/error: selalu noindex.
    $forcedRobots = trim($__env->yieldContent('robots'));
    $robots = $forcedRobots !== ''
        ? $forcedRobots
        : ((! config('seo.indexable') || request()->is(...(array) config('seo.noindex_paths', [])))
            ? 'noindex, nofollow'
            : ($brand['robots'] ?? 'index, follow'));
@endphp

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
<meta name="theme-color" content="{{ $brand['theme_color'] ?? config('branding.primary') }}" />

<title>{{ $fullTitle }}</title>

<meta name="description" content="{{ $metaDescription }}" />
<meta name="keywords" content="{{ $brand['keywords'] ?? config('seo.keywords') }}" />
<meta name="author" content="{{ $brand['author'] ?? $siteName }}" />
<meta name="application-name" content="{{ $siteName }}" />
<meta name="apple-mobile-web-app-title" content="{{ $brand['short_name'] ?? $siteName }}" />
<meta name="robots" content="{{ $robots }}" />
<link rel="canonical" href="{{ $canonical }}" />

@if (! empty($brand['google_verification']))
    <meta name="google-site-verification" content="{{ $brand['google_verification'] }}" />
@endif

{{-- Open Graph --}}
<meta property="og:type" content="website" />
<meta property="og:locale" content="{{ config('seo.locale', 'id_ID') }}" />
<meta property="og:site_name" content="{{ $siteName }}" />
<meta property="og:title" content="{{ $fullTitle }}" />
<meta property="og:description" content="{{ $metaDescription }}" />
<meta property="og:url" content="{{ $canonical }}" />
<meta property="og:image" content="{{ $ogImage }}" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta property="og:image:alt" content="{{ $siteName }}" />

{{-- Twitter / X --}}
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $fullTitle }}" />
<meta name="twitter:description" content="{{ $metaDescription }}" />
<meta name="twitter:image" content="{{ $ogImage }}" />
@if (! empty($brand['twitter']))
    <meta name="twitter:site" content="{{ \Illuminate\Support\Str::start($brand['twitter'], '@') }}" />
@endif

{{-- Favicon (?v=filemtime untuk cache busting) --}}
<link rel="icon" href="{{ $brand['favicon_url'] ?? asset('favicon.ico') }}" sizes="any" />
<link rel="icon" type="image/svg+xml" href="{{ $brand['favicon_svg_url'] ?? asset('favicon.svg') }}" />
<link rel="icon" type="image/png" sizes="32x32" href="{{ $brand['favicon_png_url'] ?? asset('favicon-32x32.png') }}" />
<link rel="apple-touch-icon" sizes="180x180" href="{{ $brand['apple_icon_url'] ?? asset('apple-touch-icon.png') }}" />
<link rel="manifest" href="{{ $brand['manifest_url'] ?? asset('site.webmanifest') }}" />

{{-- JSON-LD: Organization + WebSite + WebApplication --}}
<script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => url('/') . '#organization',
                'name' => $siteName,
                'url' => url('/'),
                'logo' => asset('android-chrome-512x512.png'),
            ],
            [
                '@type' => 'WebSite',
                '@id' => url('/') . '#website',
                'name' => $siteName,
                'url' => url('/'),
                'description' => $brand['description'] ?? config('seo.description'),
                'inLanguage' => 'id-ID',
                'publisher' => ['@id' => url('/') . '#organization'],
            ],
            [
                '@type' => 'WebApplication',
                'name' => $siteName,
                'url' => url('/'),
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web',
                'description' => $brand['description'] ?? config('seo.description'),
                'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}
</script>

<meta name="csrf-token" content="{{ csrf_token() }}" />
