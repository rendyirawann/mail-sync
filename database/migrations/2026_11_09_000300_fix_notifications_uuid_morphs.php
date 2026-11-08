<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * users.id bertipe UUID, jadi notifiable_id wajib uuid (bukan bigint).
 * PostgreSQL menolak UUID di kolom bigint → semua halaman tim error 500.
 * Notifikasi bersifat sementara, sehingga tabel aman dibuat ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('notifications');

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->uuidMorphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Tidak dikembalikan ke bigint: itu justru bug awalnya.
    }
};
