<?php

namespace Tests\Feature\Saas;

use App\Enums\RegistrationStatus;
use App\Enums\TeamRole;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Registration;
use App\Models\Setting;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * Alur bisnis: daftar publik → tagihan + WA → Superadmin setujui dengan bukti → tim aktif → owner login di /{slug}.
 */
class RegistrationFlowTest extends SaasTestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'team_name' => 'Dinas Kominfo Contoh',
            'owner_name' => 'Budi Santoso',
            'owner_email' => 'budi@example.com',
            'owner_whatsapp' => '081234567890',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
            'plan_id' => Plan::where('code', 'monthly')->value('id'),
            'agree' => '1',
            '_hp_time' => Crypt::encryptString((string) (time() - 10)),
        ], $overrides);
    }

    public function test_public_registration_shows_invoice_with_whatsapp_link(): void
    {
        Setting::set('saas_whatsapp', '6281111111111');

        $response = $this->post('/daftar', $this->payload());

        $registration = Registration::firstOrFail();
        $response->assertRedirect(route('register.team.show', $registration));
        $this->assertSame(RegistrationStatus::Pending, $registration->status);
        $this->assertSame(39000, $registration->amount);
        $this->assertSame('6281234567890', $registration->owner_whatsapp);
        $this->assertNotSame('Rahasia123', $registration->owner_password);
        $this->assertSame(0, Team::count(), 'Tim belum boleh dibuat sebelum pembayaran dikonfirmasi.');

        $this->get(route('register.team.show', $registration))
            ->assertOk()
            ->assertSee('Rp39.000')
            ->assertSee('https://wa.me/6281111111111?text=', false)
            ->assertSee($registration->code);
    }

    public function test_invoice_page_is_not_viewable_from_another_session(): void
    {
        $this->post('/daftar', $this->payload());
        $registration = Registration::firstOrFail();

        $this->flushSession();
        $this->get(route('register.team.show', $registration))->assertNotFound();
    }

    public function test_superadmin_approval_creates_team_slug_owner_and_payment(): void
    {
        Storage::fake('local');
        $this->post('/daftar', $this->payload(['plan_id' => Plan::where('code', 'lifetime')->value('id')]));
        $registration = Registration::firstOrFail();

        $this->actingAs($this->superadmin())
            ->post(route('admin.saas.registrations.approve', $registration), [
                'proof' => UploadedFile::fake()->image('bukti.png'),
                'note' => 'BCA 10.15',
            ])
            ->assertRedirect(route('admin.saas.registrations.show', $registration));

        $team = Team::firstOrFail();
        $this->assertMatchesRegularExpression('/^[a-z2-9]{4}$/', $team->slug);
        $this->assertNull($team->expires_at, 'Paket lifetime tidak punya tanggal kedaluwarsa.');

        $owner = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertSame($team->id, $owner->team_id);
        $this->assertSame(TeamRole::Owner, $owner->team_role);

        $payment = Payment::firstOrFail();
        $this->assertSame(99000, $payment->amount);
        Storage::disk('local')->assertExists($payment->proof_path);
        $this->assertSame(RegistrationStatus::Approved, $registration->fresh()->status);

        // Owner login di /{slug}/login dengan password yang diisi saat daftar.
        auth()->logout();
        $this->postJson(route('team.login.store', $team), [
            'email' => 'budi@example.com',
            'password' => 'Rahasia123',
            '_hp_time' => Crypt::encryptString((string) (time() - 10)),
        ])->assertOk()->assertJsonPath('redirect', route('team.dashboard', $team));
    }

    public function test_approval_requires_proof(): void
    {
        $this->post('/daftar', $this->payload());
        $registration = Registration::firstOrFail();

        $this->actingAs($this->superadmin())
            ->post(route('admin.saas.registrations.approve', $registration), [])
            ->assertSessionHasErrors('proof');

        $this->assertSame(0, Team::count());
    }

    public function test_proof_rejects_non_image_files(): void
    {
        $this->post('/daftar', $this->payload());
        $registration = Registration::firstOrFail();

        $this->actingAs($this->superadmin())
            ->post(route('admin.saas.registrations.approve', $registration), ['proof' => UploadedFile::fake()->create('x.php', 10, 'text/x-php')])
            ->assertSessionHasErrors('proof');
    }

    public function test_superadmin_can_register_team_directly(): void
    {
        Storage::fake('local');

        $this->actingAs($this->superadmin())->post(route('admin.saas.registrations.store'), [
            'team_name' => 'Kecamatan A',
            'owner_name' => 'Siti',
            'owner_email' => 'siti@example.com',
            'owner_whatsapp' => '081299998888',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
            'plan_id' => Plan::where('code', 'monthly')->value('id'),
            'proof' => UploadedFile::fake()->create('bukti.pdf', 50, 'application/pdf'),
        ])->assertRedirect();

        $team = Team::firstOrFail();
        $this->assertTrue($team->expires_at->between(now()->addDays(27), now()->addDays(32)));
        $this->assertSame('superadmin', Registration::firstOrFail()->source);
    }

    public function test_team_member_cannot_open_superadmin_saas_pages(): void
    {
        $team = $this->makeTeam();
        $owner = $this->member($team);

        $this->actingAs($owner)->get(route('admin.saas.teams.index'))
            ->assertRedirect(route('team.dashboard', $team));
    }
}
