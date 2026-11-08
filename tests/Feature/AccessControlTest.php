<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Route terproteksi menolak guest, user tanpa izin, dan percobaan IDOR.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_pages(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/admin/login');
        $this->get('/admin/users')->assertRedirect('/admin/login');
        $this->getJson('/admin/dashboard')->assertUnauthorized();
    }

    public function test_user_without_permission_gets_403_on_user_management(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->get('/admin/settings')->assertForbidden();
    }

    public function test_user_with_permission_can_open_user_management(): void
    {
        Permission::create(['name' => 'view_resources']);
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo('view_resources');

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->get('/admin/users')->assertOk();
    }

    public function test_user_cannot_edit_another_users_profile_or_password(): void
    {
        $attacker = User::factory()->create();
        $victim = User::factory()->create(['email' => 'korban@example.com']);

        $this->actingAs($attacker)->getJson("/admin/my-profile/{$victim->id}/edit")->assertForbidden();
        $this->actingAs($attacker)->getJson("/admin/my-security/{$victim->id}/edit")->assertForbidden();

        $this->actingAs($attacker)->putJson("/admin/my-security/{$victim->id}", [
            'email' => 'penyerang@example.com',
            'current_password' => 'password',
            'new_password' => 'Diambil123',
            'new_confirm_password' => 'Diambil123',
        ])->assertForbidden();

        $this->assertSame('korban@example.com', $victim->fresh()->email);
    }

    public function test_user_can_edit_own_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson("/admin/my-profile/{$user->id}/edit")
            ->assertOk()
            ->assertJsonStructure(['html']);
    }
}
