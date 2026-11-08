<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Setiap halaman error berstatus benar, berbahasa Indonesia, dan tidak membocorkan detail.
 * Route uji dibuat sementara di dalam test (tidak ada di routes/).
 */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function codes(): array
    {
        return [
            '400' => [400, 'Permintaan Tidak Valid'],
            '401' => [401, 'Perlu Masuk'],
            '403' => [403, 'Akses Ditolak'],
            '404' => [404, 'Halaman Tidak Ditemukan'],
            '405' => [405, 'Metode Tidak Diizinkan'],
            '408' => [408, 'Waktu Permintaan Habis'],
            '413' => [413, 'File Terlalu Besar'],
            '419' => [419, 'Sesi Kedaluwarsa'],
            '422' => [422, 'Data Tidak Valid'],
            '429' => [429, 'Terlalu Banyak Permintaan'],
            '500' => [500, 'Terjadi Kesalahan Server'],
            '502' => [502, 'Gateway Bermasalah'],
            '503' => [503, 'Sedang Pemeliharaan'],
            '504' => [504, 'Waktu Gateway Habis'],
        ];
    }

    #[DataProvider('codes')]
    public function test_error_page_renders_with_correct_status(int $code, string $title): void
    {
        Route::get('/_test/error/{code}', fn (int $code) => abort($code))->middleware('web');

        $this->get("/_test/error/{$code}")
            ->assertStatus($code)
            ->assertSee($title)
            ->assertSee('noindex, nofollow', false)
            ->assertSee(config('app.name'));
    }

    public function test_custom_403_message_is_shown(): void
    {
        Route::get('/_test/forbidden', fn () => abort(403, 'Khusus admin keuangan.'))->middleware('web');

        $this->get('/_test/forbidden')->assertForbidden()->assertSee('Khusus admin keuangan.');
    }

    public function test_framework_default_messages_are_not_leaked(): void
    {
        $this->get('/tidak-ada-sama-sekali')->assertNotFound()->assertDontSee('could not be found');
    }

    public function test_500_hides_exception_detail_when_debug_off(): void
    {
        config(['app.debug' => false]);
        Route::get('/_test/boom', fn () => throw new \RuntimeException('rahasia-internal'))->middleware('web');

        $this->get('/_test/boom')->assertStatus(500)->assertDontSee('rahasia-internal');

        $this->getJson('/_test/boom')
            ->assertStatus(500)
            ->assertJsonPath('status', 'error')
            ->assertJsonMissingPath('debug');
    }

    public function test_429_shows_retry_after_seconds(): void
    {
        Route::get('/_test/throttle', fn () => throw new ThrottleRequestsException('x', null, ['Retry-After' => 42]))
            ->middleware('web');

        $this->get('/_test/throttle')->assertStatus(429)->assertSee('42 detik');
        $this->getJson('/_test/throttle')->assertStatus(429)->assertHeader('Retry-After', 42);
    }
}
