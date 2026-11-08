<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/**
 * Honeypot & time-trap menolak bot tanpa memblokir input normal.
 */
class AntiSpamTest extends TestCase
{
    use RefreshDatabase;

    private const REJECTED = 'Permintaan tidak dapat diproses. Silakan muat ulang halaman dan coba lagi.';

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'tidak-ada@example.com',
            'password' => 'Kata\'Sandi"123 OR 1=1--',
            '_hp_time' => Crypt::encryptString((string) (time() - 10)),
        ], $overrides);
    }

    public function test_login_form_contains_honeypot_and_csrf(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('name="_token"', false)
            ->assertSee('name="website"', false)
            ->assertSee('name="_hp_time"', false);
    }

    public function test_filled_honeypot_is_rejected(): void
    {
        $this->postJson('/admin/login', $this->payload(['website' => 'http://spam.example']))
            ->assertStatus(422)
            ->assertJsonPath('message', self::REJECTED);
    }

    public function test_too_fast_submission_is_rejected(): void
    {
        $this->postJson('/admin/login', $this->payload(['_hp_time' => Crypt::encryptString((string) time())]))
            ->assertStatus(422)
            ->assertJsonPath('message', self::REJECTED);
    }

    public function test_normal_submission_with_quotes_reaches_authentication(): void
    {
        $response = $this->postJson('/admin/login', $this->payload());

        $response->assertStatus(422);
        $this->assertNotSame(self::REJECTED, $response->json('message'));
    }

    public function test_valid_login_succeeds(): void
    {
        $user = User::factory()->create(['password' => 'Rahasia123']);

        $this->postJson('/admin/login', $this->payload(['email' => $user->email, 'password' => 'Rahasia123']))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertAuthenticatedAs($user);
    }
}
