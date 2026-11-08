<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Validasi 422 untuk form web & JSON, pesan Bahasa Indonesia, dan @csrf di semua form.
 */
class ValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_form_validation_redirects_back_with_errors(): void
    {
        $this->from('/admin/forgot-password')
            ->post('/admin/forgot-password', [
                'email' => 'bukan-email',
                '_hp_time' => Crypt::encryptString((string) (time() - 10)),
            ])
            ->assertRedirect('/admin/forgot-password')
            ->assertSessionHasErrors('email');
    }

    public function test_json_validation_returns_422_with_field_errors_in_indonesian(): void
    {
        $this->postJson('/admin/login', [
            '_hp_time' => Crypt::encryptString((string) (time() - 10)),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', fn (string $message) => str_contains($message, 'wajib diisi'));
    }

    public function test_forgot_password_does_not_reveal_unknown_email(): void
    {
        $this->from('/admin/forgot-password')
            ->post('/admin/forgot-password', [
                'email' => 'tidak-terdaftar@example.com',
                '_hp_time' => Crypt::encryptString((string) (time() - 10)),
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', __('passwords.sent'));
    }

    public function test_every_mutating_form_has_csrf_token(): void
    {
        $missing = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            $html = $file->getContents();

            preg_match_all('/<form\b[^>]*>(.*?)<\/form>/is', $html, $forms, PREG_SET_ORDER);

            foreach ($forms as $form) {
                $isPost = preg_match('/method=["\']?post/i', $form[0]) === 1;

                if ($isPost && ! str_contains($form[1], '@csrf')) {
                    $missing[] = $file->getRelativePathname();
                }
            }
        }

        $this->assertSame([], $missing, 'Form POST tanpa @csrf: '.implode(', ', $missing));
    }
}
