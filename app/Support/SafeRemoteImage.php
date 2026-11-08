<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Mengambil gambar dari URL milik user (mis. logo kop di Google Drive) secara aman.
 *
 * Pengaman SSRF (OWASP A10): hanya http/https, host harus resolve ke IP publik
 * (tolak 127.0.0.1, 10.x, 192.168.x, 169.254.x, ::1, dst.), tanpa redirect,
 * batas waktu & ukuran, dan respons wajib bertipe gambar.
 */
class SafeRemoteImage
{
    private const MAX_BYTES = 1_048_576; // 1 MB

    /**
     * Ubah tautan berbagi Google Drive menjadi tautan gambar langsung.
     */
    public static function normalizeUrl(?string $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (preg_match('#drive\.google\.com/(?:file/d/|open\?id=|uc\?(?:export=\w+&)?id=)([A-Za-z0-9_-]{10,})#', $url, $m)) {
            return 'https://drive.google.com/thumbnail?id='.$m[1].'&sz=w400';
        }

        return $url;
    }

    /**
     * Data URI gambar, atau null bila URL tidak aman / gagal diambil.
     */
    public static function dataUri(?string $url): ?string
    {
        $url = self::normalizeUrl($url);

        if ($url === null || ! self::isPublicHttpUrl($url)) {
            return null;
        }

        return Cache::remember('remote-image:'.sha1($url), now()->addDay(), function () use ($url): ?string {
            try {
                $response = Http::timeout(5)->withOptions(['allow_redirects' => false])->get($url);
            } catch (\Throwable) {
                return null;
            }

            $type = strtolower((string) $response->header('Content-Type'));
            $body = $response->body();

            if (! $response->successful() || ! str_starts_with($type, 'image/') || str_contains($type, 'svg')
                || strlen($body) === 0 || strlen($body) > self::MAX_BYTES) {
                return null;
            }

            return 'data:'.strtok($type, ';').';base64,'.base64_encode($body);
        });
    }

    public static function isPublicHttpUrl(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])) {
            return false;
        }

        $host = trim($parts['host'], '[]');
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);

        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }
}
