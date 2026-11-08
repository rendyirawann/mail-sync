<?php

namespace Tests\Feature\Saas;

use App\Enums\IncomingStatus;
use App\Enums\LetterNature;
use App\Enums\TeamRole;
use App\Enums\TeamStatus;
use App\Models\IncomingLetter;
use App\Models\User;
use App\Support\Tenancy\CurrentTeam;
use Illuminate\Support\Facades\Crypt;

/**
 * Isolasi data antar tim, login per slug, kuota akun, tim beku, peran, modul.
 */
class TenancyTest extends SaasTestCase
{
    private function letterFor($team, string $subject = 'Surat rahasia'): IncomingLetter
    {
        app(CurrentTeam::class)->set($team);

        $letter = IncomingLetter::create([
            'agenda_number' => 1, 'agenda_year' => 2026, 'letter_number' => '001/X', 'letter_date' => now(),
            'received_date' => now(), 'sender' => 'Pengirim', 'subject' => $subject,
            'nature' => LetterNature::Biasa, 'status' => IncomingStatus::Baru,
        ]);

        app(CurrentTeam::class)->set(null);

        return $letter;
    }

    public function test_member_cannot_read_another_teams_letter_by_id(): void
    {
        $teamA = $this->makeTeam();
        $teamB = $this->makeTeam();
        $userA = $this->member($teamA);
        $secret = $this->letterFor($teamB);

        // URL tim sendiri + ID surat tim lain → tidak ditemukan.
        $this->actingAs($userA)->get(route('team.incoming.show', [$teamA, $secret->id]))->assertNotFound();
        // URL tim lain → dialihkan ke login tim itu.
        $this->actingAs($userA)->get(route('team.incoming.show', [$teamB, $secret->id]))->assertRedirect(route('team.login', $teamB));
    }

    public function test_lists_only_show_own_team_data(): void
    {
        $teamA = $this->makeTeam();
        $teamB = $this->makeTeam();
        $this->letterFor($teamA, 'Surat milik A');
        $this->letterFor($teamB, 'Surat milik B');

        $this->actingAs($this->member($teamA))->get(route('team.incoming.index', $teamA))
            ->assertOk()->assertSee('Surat milik A')->assertDontSee('Surat milik B');
    }

    public function test_login_only_accepts_members_of_that_team(): void
    {
        $teamA = $this->makeTeam();
        $teamB = $this->makeTeam();
        $this->member($teamB, TeamRole::Owner, ['email' => 'b@example.com', 'password' => 'Rahasia123']);

        $this->postJson(route('team.login.store', $teamA), [
            'email' => 'b@example.com', 'password' => 'Rahasia123', '_hp_time' => Crypt::encryptString((string) (time() - 10)),
        ])->assertStatus(422);
        $this->assertGuest();
    }

    public function test_team_member_cannot_login_to_admin_panel(): void
    {
        $team = $this->makeTeam();
        $this->member($team, TeamRole::Owner, ['email' => 'owner@example.com', 'password' => 'Rahasia123']);

        $this->postJson('/admin/login', [
            'email' => 'owner@example.com', 'password' => 'Rahasia123', '_hp_time' => Crypt::encryptString((string) (time() - 10)),
        ])->assertStatus(422);
        $this->assertGuest();
    }

    public function test_unknown_slug_is_404_and_suspended_team_is_403(): void
    {
        $this->get('/zzzz/login')->assertNotFound();

        $team = $this->makeTeam(attributes: ['status' => TeamStatus::Suspended]);
        $this->get(route('team.login', $team))->assertForbidden();
    }

    public function test_seat_limit_includes_owner(): void
    {
        $team = $this->makeTeam();
        $owner = $this->member($team);
        $this->member($team, TeamRole::Staf);
        $this->member($team, TeamRole::Admin);

        $this->actingAs($owner)->post(route('team.members.store', $team), [
            'name' => 'Keempat', 'email' => 'empat@example.com', 'team_role' => 'staf', 'password' => 'Rahasia123',
        ])->assertSessionHas('error');

        $this->assertSame(3, User::where('team_id', $team->id)->count());
    }

    public function test_only_owner_manages_members(): void
    {
        $team = $this->makeTeam();
        $this->member($team);
        $admin = $this->member($team, TeamRole::Admin);

        $this->actingAs($admin)->get(route('team.members.index', $team))->assertForbidden();
    }

    public function test_frozen_team_is_read_only_but_billing_stays_open(): void
    {
        $team = $this->makeTeam(attributes: ['status' => TeamStatus::Frozen]);
        $owner = $this->member($team);

        $this->actingAs($owner)->get(route('team.incoming.index', $team))->assertOk()->assertSee('data hanya bisa dilihat');
        $this->actingAs($owner)->post(route('team.incoming.store', $team), [])->assertRedirect(route('team.billing.index', $team));
        $this->actingAs($owner)->get(route('team.billing.index', $team))->assertOk();
    }

    public function test_expired_team_is_frozen_after_grace_period(): void
    {
        $expired = $this->makeTeam(attributes: ['expires_at' => now()->subDays(5)]);
        $grace = $this->makeTeam(attributes: ['expires_at' => now()->subDay()]);
        $lifetime = $this->makeTeam('lifetime');

        $this->artisan('saas:freeze-expired')->assertSuccessful();

        $this->assertSame(TeamStatus::Frozen, $expired->fresh()->status);
        $this->assertSame(TeamStatus::Active, $grace->fresh()->status);
        $this->assertSame(TeamStatus::Active, $lifetime->fresh()->status);
    }

    public function test_optional_module_locked_until_owner_enables_it(): void
    {
        $team = $this->makeTeam();
        $owner = $this->member($team);

        $this->actingAs($owner)->get(route('team.overtimes.index', $team))->assertForbidden();

        $this->actingAs($owner)->put(route('team.settings.modules', $team), ['modules' => ['lembur' => '1']])->assertRedirect();
        $this->actingAs($owner)->get(route('team.overtimes.index', $team))->assertOk();
    }

    public function test_staff_cannot_approve_outgoing_letters_or_change_settings(): void
    {
        $team = $this->makeTeam();
        $this->member($team);
        $staff = $this->member($team, TeamRole::Staf);

        $this->actingAs($staff)->get(route('team.settings.edit', $team))->assertForbidden();
        $this->actingAs($staff)->get(route('team.billing.index', $team))->assertForbidden();
    }
}
