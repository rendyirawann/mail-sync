<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Membuat akun Superadmin awal dari .env (SUPERADMIN_EMAIL, SUPERADMIN_PASSWORD).
 *
 * Tidak ada kredensial yang ditulis di kode. Bila SUPERADMIN_PASSWORD kosong,
 * dibuat password acak yang hanya ditampilkan sekali di terminal.
 * Idempoten: bila akun sudah ada, password tidak diubah.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);

        $email = (string) config('app.superadmin.email', 'superadmin@example.com');
        $password = (string) config('app.superadmin.password', '');
        $generated = $password === '';

        if ($generated) {
            $password = Str::password(16, symbols: false);
        }

        $user = User::where('email', $email)->first();
        $created = $user === null;

        $user ??= User::create([
            'name' => 'Super Administrator',
            'username' => 'superadmin',
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        if (! $user->hasRole('Superadmin')) {
            $user->assignRole($role);
        }

        if (! $created) {
            $this->command->info("Superadmin {$email} sudah ada — password tidak diubah.");

            return;
        }

        $this->command->info("Superadmin dibuat: {$email}");
        $this->command->warn($generated
            ? "Password (acak, CATAT SEKARANG — tidak ditampilkan lagi): {$password}"
            : 'Password sesuai SUPERADMIN_PASSWORD di .env — hapus nilai itu dari .env setelah login pertama.');
    }
}
