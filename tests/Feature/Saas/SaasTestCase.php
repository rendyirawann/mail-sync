<?php

namespace Tests\Feature\Saas;

use App\Enums\TeamRole;
use App\Enums\TeamStatus;
use App\Models\Plan;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Helper bersama test SaaS: membuat tim + anggota dengan peran tertentu.
 */
abstract class SaasTestCase extends TestCase
{
    use RefreshDatabase;

    protected function makeTeam(string $planCode = 'monthly', array $attributes = []): Team
    {
        $plan = Plan::where('code', $planCode)->firstOrFail();

        return Team::create($attributes + [
            'slug' => app(\App\Services\Saas\SlugGenerator::class)->generate(),
            'name' => 'Tim '.Str::random(5),
            'plan_id' => $plan->id,
            'status' => TeamStatus::Active,
            'started_at' => now(),
            'expires_at' => $plan->isLifetime() ? null : now()->addMonth(),
            'modules' => ['lembur' => false, 'perjadin' => false, 'honorarium' => false],
        ]);
    }

    protected function member(Team $team, TeamRole $role = TeamRole::Owner, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->forceFill(['team_id' => $team->id, 'team_role' => $role])->save();

        return $user->fresh();
    }

    protected function superadmin(): User
    {
        Role::findOrCreate('Superadmin');
        $user = User::factory()->create();
        $user->assignRole('Superadmin');

        return $user;
    }
}
