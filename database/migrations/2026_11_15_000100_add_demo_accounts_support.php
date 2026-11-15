<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Akun demo: tim demo sekali pakai (dihapus otomatis) + catatan pembuatan per IP
 * untuk membatasi 1 kali per 7 hari.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamp('demo_expires_at')->nullable()->index();
        });

        Schema::create('demo_generations', function (Blueprint $table) {
            $table->id();
            // IP disimpan sebagai HMAC (bukan IP mentah).
            $table->string('ip_hash', 64)->index();
            $table->uuid('team_id')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_generations');

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['is_demo', 'demo_expires_at']);
        });
    }
};
