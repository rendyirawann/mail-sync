<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fondasi SaaS: paket langganan, tim (tenant), pendaftaran, pembayaran, rekening.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();          // monthly | lifetime
            $table->string('name', 60);
            $table->unsignedInteger('price');                // rupiah
            $table->unsignedSmallInteger('duration_months')->nullable(); // null = lifetime
            $table->unsignedTinyInteger('max_members')->default(3);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 10)->unique();
            $table->string('name', 120);
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->string('status', 20)->default('active')->index(); // active | frozen | suspended
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();     // null = lifetime
            $table->timestamp('frozen_at')->nullable();
            $table->json('modules')->nullable();                      // modul opsional yang diaktifkan owner
            $table->json('profile')->nullable();                      // kop surat, logo URL, format nomor
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('team_id')->nullable()->after('id')->constrained('teams')->cascadeOnDelete();
            $table->string('team_role', 20)->nullable()->after('team_id');
            $table->index(['team_id', 'team_role']);
        });

        Schema::create('registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 20)->unique();
            $table->string('team_name', 120);
            $table->string('owner_name', 100);
            $table->string('owner_email');
            $table->string('owner_whatsapp', 20);
            $table->string('owner_password');                // sudah di-hash
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->unsignedInteger('amount');
            $table->string('source', 20)->default('public');  // public | superadmin
            $table->string('status', 20)->default('pending')->index(); // pending | approved | rejected
            $table->foreignUuid('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignUuid('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->string('note')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->foreignUuid('registration_id')->nullable()->constrained('registrations')->nullOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->string('type', 20);                       // registration | renewal | upgrade
            $table->unsignedInteger('amount');
            $table->string('proof_path')->nullable();         // disk "local" (privat)
            $table->string('proof_mime', 60)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();      // null = lifetime
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'paid_at']);
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name', 60);
            $table->string('account_number', 40);
            $table->string('account_holder', 100);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('plans')->insert([
            ['code' => 'monthly', 'name' => 'Bulanan', 'price' => 39000, 'duration_months' => 1, 'max_members' => 3,
                'description' => 'Bayar per bulan, bisa berhenti kapan saja.', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'lifetime', 'name' => 'Lifetime', 'price' => 99000, 'duration_months' => null, 'max_members' => 3,
                'description' => 'Sekali bayar, aktif selamanya.', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'team_role']);
            $table->dropConstrainedForeignId('team_id');
            $table->dropColumn('team_role');
        });

        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('registrations');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('plans');
    }
};
