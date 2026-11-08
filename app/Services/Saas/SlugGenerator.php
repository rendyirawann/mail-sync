<?php

namespace App\Services\Saas;

use App\Models\Team;

/**
 * Membuat slug tim unik 4 karakter, mis. "k7qp".
 *
 * Karakter yang mudah tertukar (0/o, 1/l/i) dibuang agar mudah dibacakan
 * lewat WhatsApp/telepon. Kata yang dipakai sistem sebagai path tidak boleh
 * menjadi slug.
 */
class SlugGenerator
{
    private const ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    /** Path milik aplikasi yang tidak boleh dipakai sebagai slug tim. */
    public const RESERVED = [
        'admin', 'api', 'app', 'auth', 'build', 'daftar', 'login', 'logout', 'up', 'storage',
        'assets', 'css', 'js', 'img', 'verifikasi', 'sitemap', 'robots', 'broadcasting',
        'harga', 'bantuan', 'syarat', 'privasi', 'kontak', 'www', 'mail', 'help',
    ];

    public function __construct(private readonly int $length = 4) {}

    /**
     * @throws \RuntimeException Bila gagal menemukan slug unik setelah banyak percobaan.
     */
    public function generate(): string
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $slug = $this->random();

            if (! in_array($slug, self::RESERVED, true) && ! Team::withTrashed()->where('slug', $slug)->exists()) {
                return $slug;
            }
        }

        throw new \RuntimeException('Gagal membuat slug tim yang unik. Coba lagi.');
    }

    private function random(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $slug = '';

        for ($i = 0; $i < $this->length; $i++) {
            $slug .= self::ALPHABET[random_int(0, $max)];
        }

        return $slug;
    }
}
