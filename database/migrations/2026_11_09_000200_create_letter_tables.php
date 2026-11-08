<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul manajemen surat per tim. Semua tabel ber-team_id (isolasi tenant).
 * Lampiran disimpan sebagai tautan (Google Drive) — tidak ada upload ke server.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------------- Master data ----------------
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('team_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 20)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['team_id', 'sort_order']);
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete(); // bila pegawai punya akun
            $table->string('name', 100);
            $table->string('nip', 30)->nullable();
            $table->string('position', 100)->nullable();   // jabatan
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['team_id', 'is_active']);
        });

        Schema::create('letter_classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('team_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->timestamps();
            $table->unique(['team_id', 'code']);
        });

        Schema::create('letter_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('team_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->timestamps();
            $table->index('team_id');
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('team_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'name']);
        });

        // ---------------- Penomoran ----------------
        Schema::create('numbering_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('team_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);         // incoming | outgoing
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['team_id', 'type', 'year']);
        });

        // ---------------- Surat masuk & disposisi ----------------
        Schema::create('incoming_letters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('agenda_number');
            $table->unsignedSmallInteger('agenda_year');
            $table->string('letter_number', 100);
            $table->date('letter_date');
            $table->date('received_date');
            $table->string('sender', 150);
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->text('summary')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('letter_categories')->nullOnDelete();
            $table->foreignId('classification_id')->nullable()->constrained('letter_classifications')->nullOnDelete();
            $table->string('nature', 20)->default('biasa');
            $table->string('status', 20)->default('baru');
            $table->string('attachment_url', 500)->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['team_id', 'agenda_year', 'agenda_number']);
            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'received_date']);
        });

        Schema::create('dispositions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('incoming_letter_id')->constrained()->cascadeOnDelete();
            $table->uuid('parent_id')->nullable(); // FK ditambahkan setelah PK ada (lihat di bawah)
            $table->foreignUuid('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('to_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->text('instruction');
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('baru');   // baru | diproses | selesai
            $table->text('response')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'status', 'due_date']);
        });

        // PostgreSQL: FK ke tabel sendiri harus dibuat setelah primary key terbentuk.
        Schema::table('dispositions', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('dispositions')->nullOnDelete();
        });

        // ---------------- Template & surat keluar ----------------
        Schema::create('letter_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->nullable()->constrained()->cascadeOnDelete(); // null = template bawaan
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->string('paper', 10)->default('A4');    // A4 | F4
            $table->longText('body');                       // HTML dengan penanda {{kunci}}
            $table->json('fields');                         // [{key,label,type,required}]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['team_id', 'is_active']);
        });

        Schema::create('outgoing_letters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained()->cascadeOnDelete();
            $table->string('letter_number', 100)->nullable();
            $table->unsignedInteger('sequence')->nullable();
            $table->unsignedSmallInteger('sequence_year')->nullable();
            $table->date('letter_date');
            $table->string('recipient', 150);
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->foreignId('category_id')->nullable()->constrained('letter_categories')->nullOnDelete();
            $table->foreignId('classification_id')->nullable()->constrained('letter_classifications')->nullOnDelete();
            $table->string('nature', 20)->default('biasa');
            $table->foreignUuid('template_id')->nullable()->constrained('letter_templates')->nullOnDelete();
            $table->json('content')->nullable();            // nilai isian template
            $table->foreignUuid('signer_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('status', 20)->default('draf');  // draf | menunggu | disetujui | ditolak | terkirim
            $table->foreignUuid('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('approval_note')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('attachment_url', 500)->nullable();
            $table->string('verification_token', 40)->nullable()->unique();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'letter_date']);
        });

        // ---------------- Modul opsional ----------------
        Schema::create('overtimes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('hours', 5, 2);
            $table->string('activity');
            $table->string('attachment_url', 500)->nullable();
            $table->string('status', 20)->default('diajukan'); // diajukan | disetujui | ditolak
            $table->timestamps();
            $table->index(['team_id', 'date']);
        });

        Schema::create('business_trips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('outgoing_letter_id')->nullable()->constrained()->nullOnDelete(); // surat tugas
            $table->string('destination', 150);
            $table->string('purpose');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedBigInteger('cost')->default(0);
            $table->string('attachment_url', 500)->nullable();
            $table->string('status', 20)->default('direncanakan'); // direncanakan | berlangsung | selesai | batal
            $table->timestamps();
            $table->index(['team_id', 'start_date']);
        });

        Schema::create('honoraria', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('employee_id')->constrained()->cascadeOnDelete();
            $table->string('activity');
            $table->date('period');                         // tanggal 1 bulan terkait
            $table->unsignedBigInteger('gross_amount');
            $table->unsignedTinyInteger('tax_percent')->default(0);
            $table->unsignedBigInteger('net_amount');
            $table->string('attachment_url', 500)->nullable();
            $table->string('status', 20)->default('belum_dibayar'); // belum_dibayar | dibayar
            $table->timestamps();
            $table->index(['team_id', 'period']);
        });
    }

    public function down(): void
    {
        foreach (['honoraria', 'business_trips', 'overtimes', 'outgoing_letters', 'letter_templates', 'dispositions',
            'incoming_letters', 'numbering_counters', 'contacts', 'letter_categories', 'letter_classifications',
            'employees', 'divisions'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
