<?php

namespace Database\Seeders;

use App\Services\Saas\DemoAccountService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role penanda akun demo. Tidak membawa permission platform apa pun: akses
 * fitur tim tetap diatur oleh team_role (Owner/Admin/Pimpinan/Staf), dan
 * panel superadmin tertutup karena akun demo selalu anggota tim.
 * Idempoten.
 */
class DemoRoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => DemoAccountService::ROLE, 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
