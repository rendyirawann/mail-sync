<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Memastikan header keamanan terpasang di HTML, JSON, dan halaman error.
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_html_response_has_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringNotContainsString('nonce-', $csp);
        $this->assertNotNull($response->headers->get('Permissions-Policy'));
        $this->assertNull($response->headers->get('Cross-Origin-Resource-Policy'));
        $this->assertNull($response->headers->get('X-Powered-By'));
    }

    public function test_headers_are_present_on_error_and_json_responses(): void
    {
        $this->get('/halaman-tidak-ada')
            ->assertNotFound()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->getJson('/admin/dashboard')
            ->assertUnauthorized()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_hsts_is_not_sent_over_plain_http(): void
    {
        config(['security.hsts' => true]);

        $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
    }
}
