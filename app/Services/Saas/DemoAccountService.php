<?php

namespace App\Services\Saas;

use App\Enums\DispositionStatus;
use App\Enums\IncomingStatus;
use App\Enums\LetterNature;
use App\Enums\OutgoingStatus;
use App\Enums\TeamRole;
use App\Enums\TeamStatus;
use App\Models\Contact;
use App\Models\Disposition;
use App\Models\Division;
use App\Models\Employee;
use App\Models\IncomingLetter;
use App\Models\LetterCategory;
use App\Models\LetterClassification;
use App\Models\OutgoingLetter;
use App\Models\Plan;
use App\Models\Team;
use App\Models\User;
use App\Support\Tenancy\CurrentTeam;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Membuat tim demo sekali pakai beserta akun tiap peran dan data contoh,
 * lalu menghapusnya kembali setelah masa berlaku habis.
 *
 * - Satu IP hanya boleh membuat demo 1 kali per 7 hari (tabel demo_generations,
 *   IP disimpan sebagai HMAC).
 * - Password hanya ada di cache (terenkripsi) selama 3 menit; di DB tersimpan hash.
 * - Tim demo dan seluruh datanya dihapus 24 jam setelah dibuat (demo:purge).
 */
class DemoAccountService
{
    /** Nama role penanda akun demo (Spatie, tanpa permission). */
    public const ROLE = 'demo';

    /** Masa hidup tim demo (jam). */
    public const LIFETIME_HOURS = 24;

    /** Jeda minimum antar pembuatan dari IP yang sama (hari). */
    public const IP_COOLDOWN_DAYS = 7;

    /** Lama kredensial bisa dilihat ulang (detik). */
    public const CREDENTIAL_TTL = 180;

    /** Batas total tim demo baru per hari (pengaman dari banyak IP). */
    public const DAILY_GLOBAL_LIMIT = 200;

    /** Domain email akun demo (domain cadangan, tidak pernah menerima email). */
    private const EMAIL_DOMAIN = 'demo.mailsync.test';

    public function __construct(
        private readonly SlugGenerator $slugs,
        private readonly CurrentTeam $currentTeam,
    ) {}

    /**
     * Waktu IP ini boleh membuat demo lagi, atau null bila sudah boleh.
     */
    public function nextAllowedAt(string $ip): ?Carbon
    {
        $last = DB::table('demo_generations')
            ->where('ip_hash', $this->ipHash($ip))
            ->where('created_at', '>', now()->subDays(self::IP_COOLDOWN_DAYS))
            ->max('created_at');

        return $last === null ? null : Carbon::parse($last)->addDays(self::IP_COOLDOWN_DAYS);
    }

    /**
     * Buat tim demo + akun. Mengembalikan token untuk mengambil kredensial dari cache.
     *
     * @throws ValidationException bila IP masih dalam masa tunggu atau kuota harian habis.
     */
    public function generate(string $ip): string
    {
        $lock = Cache::lock('demo:generate:'.$this->ipHash($ip), 30);

        if (! $lock->get()) {
            throw ValidationException::withMessages(['demo' => 'Permintaan sebelumnya masih diproses. Tunggu sebentar.']);
        }

        try {
            if ($next = $this->nextAllowedAt($ip)) {
                throw ValidationException::withMessages([
                    'demo' => 'Akun demo dari jaringan Anda sudah dibuat minggu ini. Anda bisa membuat lagi pada '
                        .$next->timezone(config('app.timezone'))->translatedFormat('d F Y, H:i').'.',
                ]);
            }

            $today = DB::table('demo_generations')->where('created_at', '>', now()->subDay())->count();
            if ($today >= self::DAILY_GLOBAL_LIMIT) {
                throw ValidationException::withMessages(['demo' => 'Kuota akun demo hari ini sudah habis. Silakan coba lagi besok.']);
            }

            [$team, $accounts] = DB::transaction(fn (): array => $this->provision($ip));
        } finally {
            $lock->release();
        }

        $token = Str::random(40);
        Cache::put($this->cacheKey($token), Crypt::encryptString(json_encode([
            'team' => $team->name,
            'code' => $team->slug,
            'login_url' => $team->loginUrl(),
            'expires_at' => $team->demo_expires_at->toIso8601String(),
            'accounts' => $accounts,
        ], JSON_THROW_ON_ERROR)), self::CREDENTIAL_TTL);
        Cache::put($this->cacheKey($token).':exp', time() + self::CREDENTIAL_TTL, self::CREDENTIAL_TTL);

        Log::channel('security')->info('Tim demo dibuat', ['team' => $team->slug]);

        return $token;
    }

    /**
     * Ambil kredensial dari cache (null bila sudah lewat 3 menit).
     *
     * @return array{team:string, code:string, login_url:string, expires_at:string, accounts:list<array{role:string, name:string, email:string, password:string}>}|null
     */
    public function credentials(?string $token): ?array
    {
        if ($token === null || $token === '') {
            return null;
        }

        $payload = Cache::get($this->cacheKey($token));
        if (! is_string($payload)) {
            return null;
        }

        try {
            return json_decode(Crypt::decryptString($payload), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }
    }

    /** Sisa detik kredensial bisa dilihat (0 bila sudah hilang). */
    public function credentialsSecondsLeft(?string $token): int
    {
        $expires = $token ? Cache::get($this->cacheKey($token).':exp') : null;

        return $expires ? max(0, (int) $expires - time()) : 0;
    }

    /**
     * Hapus semua tim demo yang sudah kedaluwarsa. Mengembalikan jumlah tim terhapus.
     */
    public function purgeExpired(): int
    {
        $count = 0;

        Team::withTrashed()
            ->where('is_demo', true)
            ->where('demo_expires_at', '<=', now())
            ->each(function (Team $team) use (&$count): void {
                $this->purge($team);
                $count++;
            });

        // Catatan per IP hanya perlu disimpan selama masa tunggu.
        DB::table('demo_generations')->where('created_at', '<', now()->subDays(self::IP_COOLDOWN_DAYS + 1))->delete();

        return $count;
    }

    /**
     * Hapus total satu tim demo beserta akun, data, notifikasi, sesi, log, dan berkas.
     */
    public function purge(Team $team): void
    {
        if (! $team->is_demo) {
            throw new \LogicException('Hanya tim demo yang boleh dihapus otomatis.');
        }

        DB::transaction(function () use ($team): void {
            $userIds = User::withoutGlobalScopes()->where('team_id', $team->id)->pluck('id')->all();
            $avatars = User::withoutGlobalScopes()->whereIn('id', $userIds)->whereNotNull('avatar')->pluck('avatar')->all();
            $userClass = (new User)->getMorphClass();

            if ($userIds !== []) {
                DB::table('notifications')->where('notifiable_type', $userClass)->whereIn('notifiable_id', $userIds)->delete();
                DB::table('sessions')->whereIn('user_id', $userIds)->delete();
                DB::table('model_has_roles')->where('model_type', $userClass)->whereIn('model_id', $userIds)->delete();
                DB::table('model_has_permissions')->where('model_type', $userClass)->whereIn('model_id', $userIds)->delete();
                // bannable_id bisa bigint (bawaan paket) -> bandingkan sebagai teks.
                DB::table('bans')->where('bannable_type', $userClass)
                    ->whereRaw('CAST(bannable_id AS VARCHAR(64)) IN ('.implode(',', array_fill(0, count($userIds), '?')).')', $userIds)
                    ->delete();
                DB::table('activity_log')->where('causer_type', $userClass)->whereIn('causer_id', $userIds)->delete();
                DB::table('password_reset_tokens')->whereIn('email', User::withoutGlobalScopes()->whereIn('id', $userIds)->pluck('email'))->delete();
            }

            // Log yang subjeknya data tim ini (surat, disposisi, tim itu sendiri).
            $subjectIds = collect([
                IncomingLetter::class, OutgoingLetter::class, Disposition::class,
            ])->flatMap(fn (string $model) => $model::withoutGlobalScopes()->where('team_id', $team->id)->pluck('id'))
                ->push($team->id)->merge($userIds)->all();
            DB::table('activity_log')->whereIn('subject_id', $subjectIds)->delete();

            // Relasi tim (surat, pegawai, master data, akun) terhapus lewat FK cascade.
            $team->forceDelete();

            foreach ($avatars as $avatar) {
                Storage::disk('public')->delete($avatar);
            }
        });

        Log::channel('security')->info('Tim demo dihapus', ['team' => $team->slug]);
    }

    /**
     * @return array{0: Team, 1: list<array{role:string, name:string, email:string, password:string}>}
     */
    private function provision(string $ip): array
    {
        $plan = Plan::query()->where('is_active', true)->orderByRaw('duration_months is null')->orderBy('id')->firstOrFail();
        $now = now();

        $team = new Team([
            'slug' => $this->slugs->generate(),
            'name' => 'Tim Demo '.Str::upper(Str::random(4)),
            'email' => null,
            'plan_id' => $plan->id,
            'status' => TeamStatus::Active,
            'started_at' => $now,
            'expires_at' => $now->copy()->addHours(self::LIFETIME_HOURS),
            'modules' => array_fill_keys(array_keys(Team::OPTIONAL_MODULES), true),
        ]);
        $team->forceFill(['is_demo' => true, 'demo_expires_at' => $now->copy()->addHours(self::LIFETIME_HOURS)])->save();

        DB::table('demo_generations')->insert([
            'ip_hash' => $this->ipHash($ip),
            'team_id' => $team->id,
            'created_at' => $now,
        ]);

        $previous = $this->currentTeam->get();
        $this->currentTeam->set($team);
        URL::defaults(['team' => $team->slug]);

        try {
            $accounts = [];
            $users = [];

            foreach ([TeamRole::Owner, TeamRole::Admin, TeamRole::Pimpinan, TeamRole::Staf] as $role) {
                $password = Str::password(12, symbols: false);
                $user = new User([
                    'name' => $role->label().' Demo',
                    'username' => $role->value.'_'.$team->slug,
                    'email' => $role->value.'.'.$team->slug.'@'.self::EMAIL_DOMAIN,
                    'is_active' => true,
                    'password' => $password,
                ]);
                $user->forceFill([
                    'team_id' => $team->id,
                    'team_role' => $role,
                    'email_verified_at' => $now,
                ])->save();
                $user->assignRole(self::ROLE);

                $users[$role->value] = $user;
                $accounts[] = ['role' => $role->label(), 'name' => $user->name, 'email' => $user->email, 'password' => $password];
            }

            $this->seedSampleData($team, $users);
        } finally {
            $this->currentTeam->set($previous);
        }

        return [$team, $accounts];
    }

    /**
     * Data contoh agar fitur langsung bisa dicoba.
     *
     * @param  array<string, User>  $users
     */
    private function seedSampleData(Team $team, array $users): void
    {
        $year = (int) now()->format('Y');

        $umum = Division::create(['name' => 'Tata Usaha', 'code' => 'TU', 'sort_order' => 1]);
        $keu = Division::create(['name' => 'Keuangan', 'code' => 'KEU', 'sort_order' => 2]);

        $employees = [
            'pimpinan' => Employee::create(['division_id' => $umum->id, 'user_id' => $users['pimpinan']->id, 'name' => $users['pimpinan']->name, 'position' => 'Kepala Kantor', 'is_active' => true]),
            'staf' => Employee::create(['division_id' => $umum->id, 'user_id' => $users['staf']->id, 'name' => $users['staf']->name, 'position' => 'Staf Tata Usaha', 'is_active' => true]),
            'admin' => Employee::create(['division_id' => $keu->id, 'user_id' => $users['admin']->id, 'name' => $users['admin']->name, 'position' => 'Bendahara', 'is_active' => true]),
        ];

        $kategori = LetterCategory::create(['name' => 'Undangan']);
        LetterCategory::create(['name' => 'Pemberitahuan']);
        $klasifikasi = LetterClassification::create(['code' => '005', 'name' => 'Undangan']);
        LetterClassification::create(['code' => '900', 'name' => 'Keuangan']);

        $senders = [
            ['Dinas Pendidikan', 'Undangan Rapat Koordinasi Evaluasi Semester', LetterNature::Segera, IncomingStatus::Didisposisi],
            ['Biro Keuangan Daerah', 'Pedoman Penyusunan Laporan Anggaran Triwulan', LetterNature::Biasa, IncomingStatus::Selesai],
            ['Penyedia Layanan Jaringan', 'Pemberitahuan Pemeliharaan Jaringan Intranet', LetterNature::Biasa, IncomingStatus::Baru],
        ];

        foreach ($senders as $i => [$sender, $subject, $nature, $status]) {
            $contact = Contact::create(['name' => $sender]);
            $letter = IncomingLetter::create([
                'agenda_number' => $i + 1,
                'agenda_year' => $year,
                'letter_number' => sprintf('%03d/DEMO/%s', $i + 1, $year),
                'letter_date' => now()->subDays(5 - $i)->toDateString(),
                'received_date' => now()->subDays(4 - $i)->toDateString(),
                'sender' => $sender,
                'contact_id' => $contact->id,
                'subject' => $subject,
                'summary' => 'Contoh surat untuk mencoba fitur. Data ini dihapus otomatis.',
                'category_id' => $kategori->id,
                'classification_id' => $klasifikasi->id,
                'nature' => $nature,
                'status' => $status,
                'created_by' => $users['staf']->id,
            ]);

            if ($status !== IncomingStatus::Baru) {
                Disposition::create([
                    'incoming_letter_id' => $letter->id,
                    'from_user_id' => $users['pimpinan']->id,
                    'to_employee_id' => $employees['staf']->id,
                    'instruction' => 'Mohon ditindaklanjuti dan laporkan hasilnya.',
                    'due_date' => now()->addDays(3)->toDateString(),
                    'status' => $status === IncomingStatus::Selesai ? DispositionStatus::Selesai : DispositionStatus::Baru,
                    'completed_at' => $status === IncomingStatus::Selesai ? now() : null,
                ]);
            }
        }

        DB::table('numbering_counters')->insert([
            'team_id' => $team->id,
            'type' => 'incoming',
            'year' => $year,
            'last_number' => count($senders),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        OutgoingLetter::create([
            'letter_date' => now()->toDateString(),
            'recipient' => 'Dinas Pendidikan',
            'subject' => 'Konfirmasi Kehadiran Rapat Koordinasi',
            'category_id' => $kategori->id,
            'classification_id' => $klasifikasi->id,
            'nature' => LetterNature::Biasa,
            'content' => '<p>Dengan hormat, kami mengonfirmasi kehadiran pada rapat koordinasi.</p>',
            'signer_employee_id' => $employees['pimpinan']->id,
            'status' => OutgoingStatus::Draf,
            'created_by' => $users['staf']->id,
        ]);
    }

    private function cacheKey(string $token): string
    {
        return 'demo:credentials:'.hash('sha256', $token);
    }

    private function ipHash(string $ip): string
    {
        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
