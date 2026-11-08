<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Beranda publik tampil dan menampilkan paket harga dari database.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Rp39.000')
            ->assertSee('Rp99.000');
    }
}
