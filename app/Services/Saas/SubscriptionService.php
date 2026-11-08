<?php

namespace App\Services\Saas;

use App\Enums\PaymentType;
use App\Enums\RegistrationStatus;
use App\Enums\TeamRole;
use App\Enums\TeamStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Registration;
use App\Models\Team;
use App\Models\User;
use App\Support\ActivityRecorder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Aturan bisnis langganan: pendaftaran → aktivasi → perpanjangan → beku.
 *
 * Semua perubahan tim/akun/pembayaran dibungkus transaksi agar tidak pernah
 * ada tim aktif tanpa owner atau pembayaran tanpa tim.
 */
class SubscriptionService
{
    /** Hari toleransi setelah jatuh tempo sebelum tim dibekukan. */
    public const GRACE_DAYS = 3;

    public function __construct(private readonly SlugGenerator $slugs) {}

    /**
     * Simpan pendaftaran publik (status menunggu pembayaran).
     *
     * @param  array{team_name: string, owner_name: string, owner_email: string, owner_whatsapp: string, password: string, plan_id: int}  $data
     */
    public function register(array $data, string $source = 'public', ?string $ip = null): Registration
    {
        $plan = Plan::active()->findOrFail($data['plan_id']);

        return Registration::create([
            'code' => $this->registrationCode(),
            'team_name' => $data['team_name'],
            'owner_name' => $data['owner_name'],
            'owner_email' => Str::lower($data['owner_email']),
            'owner_whatsapp' => $this->normalizeWhatsapp($data['owner_whatsapp']),
            'owner_password' => bcrypt($data['password']),
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'source' => $source,
            'status' => RegistrationStatus::Pending,
            'ip_address' => $ip,
        ]);
    }

    /**
     * Setujui pendaftaran: buat tim + slug + akun owner + catat pembayaran.
     *
     * @throws ValidationException Bila email owner sudah dipakai atau pendaftaran sudah diproses.
     */
    public function approve(Registration $registration, User $admin, ?UploadedFile $proof, ?string $note = null): Team
    {
        if ($registration->status !== RegistrationStatus::Pending) {
            throw ValidationException::withMessages(['registration' => 'Pendaftaran ini sudah diproses sebelumnya.']);
        }

        if (User::where('email', $registration->owner_email)->exists()) {
            throw ValidationException::withMessages(['owner_email' => 'Email owner sudah terdaftar pada akun lain.']);
        }

        return DB::transaction(function () use ($registration, $admin, $proof, $note): Team {
            $plan = $registration->plan;
            $now = now();

            $team = Team::create([
                'slug' => $this->slugs->generate(),
                'name' => $registration->team_name,
                'email' => $registration->owner_email,
                'phone' => $registration->owner_whatsapp,
                'plan_id' => $plan->id,
                'status' => TeamStatus::Active,
                'started_at' => $now,
                'expires_at' => $this->periodEnd($plan, $now),
                'modules' => array_fill_keys(array_keys(Team::OPTIONAL_MODULES), false),
            ]);

            $owner = new User([
                'name' => $registration->owner_name,
                'username' => $this->uniqueUsername($registration->owner_email),
                'email' => $registration->owner_email,
                'no_wa' => $registration->owner_whatsapp,
                'is_active' => true,
            ]);
            // Password sudah di-hash saat pendaftaran — set langsung tanpa cast "hashed" ulang.
            $owner->forceFill([
                'password' => $registration->owner_password,
                'team_id' => $team->id,
                'team_role' => TeamRole::Owner,
                'email_verified_at' => $now,
            ])->save();

            $this->recordPayment($team, $plan, PaymentType::Registration, $admin, $proof, $now, $note, $registration);

            $registration->forceFill([
                'status' => RegistrationStatus::Approved,
                'team_id' => $team->id,
                'processed_by' => $admin->id,
                'processed_at' => $now,
                'note' => $note,
            ])->save();

            ActivityRecorder::log('langganan', "Mengaktifkan tim {$team->name} ({$team->slug}) paket {$plan->name}");
            Log::channel('security')->info('Tim diaktifkan', ['team' => $team->slug, 'by' => $admin->id]);

            return $team;
        });
    }

    public function reject(Registration $registration, User $admin, ?string $note): void
    {
        $registration->forceFill([
            'status' => RegistrationStatus::Rejected,
            'processed_by' => $admin->id,
            'processed_at' => now(),
            'note' => $note,
        ])->save();
    }

    /**
     * Perpanjang / ganti paket tim. Masa aktif dihitung dari jatuh tempo
     * lama bila belum lewat (tidak ada hari yang hilang), atau dari hari ini.
     */
    public function renew(Team $team, Plan $plan, User $admin, ?UploadedFile $proof, ?string $note = null): Payment
    {
        return DB::transaction(function () use ($team, $plan, $admin, $proof, $note): Payment {
            $start = ($team->expires_at !== null && $team->expires_at->isFuture()) ? $team->expires_at->copy() : now();
            $type = ($plan->isLifetime() && ! $team->plan->isLifetime()) ? PaymentType::Upgrade : PaymentType::Renewal;

            $payment = $this->recordPayment($team, $plan, $type, $admin, $proof, $start, $note);

            $team->forceFill([
                'plan_id' => $plan->id,
                'expires_at' => $this->periodEnd($plan, $start),
                'status' => TeamStatus::Active,
                'frozen_at' => null,
            ])->save();

            ActivityRecorder::log('langganan', "Memperpanjang tim {$team->name} ({$team->slug}) paket {$plan->name}");

            return $payment;
        });
    }

    /**
     * Bekukan tim yang lewat jatuh tempo + masa tenggang. Dipanggil scheduler harian.
     *
     * @return int Jumlah tim yang dibekukan.
     */
    public function freezeExpired(): int
    {
        $limit = now()->subDays(self::GRACE_DAYS);

        return Team::query()
            ->where('status', TeamStatus::Active->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $limit)
            ->update(['status' => TeamStatus::Frozen->value, 'frozen_at' => now()]);
    }

    public function setStatus(Team $team, TeamStatus $status): void
    {
        $team->forceFill([
            'status' => $status,
            'frozen_at' => $status === TeamStatus::Frozen ? now() : null,
        ])->save();

        ActivityRecorder::log('langganan', "Mengubah status tim {$team->slug} menjadi {$status->label()}");
    }

    /** Akhir periode; null untuk lifetime. */
    public function periodEnd(Plan $plan, Carbon $start): ?Carbon
    {
        return $plan->isLifetime() ? null : $start->copy()->addMonthsNoOverflow((int) $plan->duration_months);
    }

    /** 08xx / +62 / 62 → 62xx (format wa.me). */
    public function normalizeWhatsapp(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return $digits;
    }

    private function recordPayment(
        Team $team,
        Plan $plan,
        PaymentType $type,
        User $admin,
        ?UploadedFile $proof,
        Carbon $start,
        ?string $note,
        ?Registration $registration = null,
    ): Payment {
        $path = null;
        $mime = null;

        if ($proof !== null) {
            $mime = $proof->getMimeType();
            // Disk "local" = storage/app/private: bukti transfer tidak pernah bisa diakses publik.
            $path = $proof->storeAs(
                'payment-proofs/'.now()->format('Y/m'),
                Str::uuid()->toString().'.'.($proof->guessExtension() ?: 'bin'),
                'local',
            );
        }

        return Payment::create([
            'team_id' => $team->id,
            'registration_id' => $registration?->id,
            'plan_id' => $plan->id,
            'type' => $type,
            'amount' => $plan->price,
            'proof_path' => $path ?: null,
            'proof_mime' => $mime,
            'paid_at' => now(),
            'period_start' => $start,
            'period_end' => $this->periodEnd($plan, $start),
            'verified_by' => $admin->id,
            'note' => $note,
        ]);
    }

    private function registrationCode(): string
    {
        do {
            $code = 'REG-'.Str::upper(Str::random(6));
        } while (Registration::where('code', $code)->exists());

        return $code;
    }

    private function uniqueUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@'), '_') ?: 'user';
        $username = $base;

        while (User::where('username', $username)->exists()) {
            $username = $base.'_'.Str::lower(Str::random(4));
        }

        return $username;
    }

    /** Hapus file bukti (dipakai bila perlu membersihkan). */
    public function deleteProof(Payment $payment): void
    {
        if ($payment->proof_path) {
            Storage::disk('local')->delete($payment->proof_path);
        }
    }
}
