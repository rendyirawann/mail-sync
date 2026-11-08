<?php

namespace App\Http\Controllers\Team;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Support\ActivityRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Owner mengelola akun anggota tim. Kuota akun (termasuk owner) mengikuti paket.
 */
class MemberController extends Controller
{
    public function index(Team $team): View
    {
        return view('team.members.index', [
            'members' => $team->users()->orderByRaw("team_role = 'owner' desc")->orderBy('name')->get(),
            'roles' => TeamRole::assignable(),
            'max' => $team->plan->max_members,
        ]);
    }

    public function store(Request $request, Team $team): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'no_wa' => ['nullable', 'string', 'regex:/^(\+?62|0)8[0-9]{7,12}$/'],
            'team_role' => ['required', Rule::in(array_map(fn (TeamRole $r) => $r->value, TeamRole::assignable()))],
            'password' => ['required', Password::defaults()],
        ], ['email.unique' => 'Email ini sudah dipakai akun lain.', 'no_wa.regex' => 'Nomor WhatsApp tidak valid.'], [
            'name' => 'nama', 'team_role' => 'peran', 'no_wa' => 'nomor WhatsApp',
        ]);

        // Kunci baris tim agar dua penambahan bersamaan tidak melampaui kuota.
        $member = DB::transaction(function () use ($team, $data): ?User {
            $locked = Team::whereKey($team->id)->lockForUpdate()->first();

            if ($locked === null || $locked->users()->count() >= $team->plan->max_members) {
                return null;
            }

            $user = new User([
                'name' => $data['name'],
                'email' => Str::lower($data['email']),
                'username' => $this->uniqueUsername($data['email']),
                'no_wa' => $data['no_wa'] ?? null,
                'password' => $data['password'],
                'is_active' => true,
            ]);
            $user->forceFill(['team_id' => $team->id, 'team_role' => $data['team_role'], 'email_verified_at' => now()])->save();

            return $user;
        });

        if ($member === null) {
            return back()->withInput()->with('error', "Kuota akun paket {$team->plan->name} sudah penuh ({$team->plan->max_members} akun termasuk owner).");
        }

        ActivityRecorder::log('anggota tim', "Menambahkan anggota {$member->name} sebagai {$member->team_role->label()}");

        return back()->with('success', "Akun {$member->name} dibuat. Bagikan tautan login: ".route('team.login', $team));
    }

    public function update(Request $request, Team $team, User $member): RedirectResponse
    {
        abort_if($member->isOwner(), 403, 'Peran owner tidak dapat diubah.');

        $data = $request->validate([
            'team_role' => ['required', Rule::in(array_map(fn (TeamRole $r) => $r->value, TeamRole::assignable()))],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $member->forceFill(['team_role' => $data['team_role'], 'is_active' => $request->boolean('is_active')])->save();
        ActivityRecorder::log('anggota tim', "Mengubah peran/status {$member->name}");

        return back()->with('success', 'Anggota diperbarui.');
    }

    public function resetPassword(Request $request, Team $team, User $member): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', Password::defaults()]]);

        $member->forceFill(['password' => $data['password']])->save();
        ActivityRecorder::log('anggota tim', "Mengatur ulang password {$member->name}");

        return back()->with('success', "Password {$member->name} diperbarui. Sampaikan secara langsung, bukan di grup.");
    }

    public function destroy(Request $request, Team $team, User $member): RedirectResponse
    {
        abort_if($member->isOwner() || $member->is($request->user()), 403, 'Owner dan akun Anda sendiri tidak dapat dihapus.');

        ActivityRecorder::log('anggota tim', "Menghapus anggota {$member->name}");
        $member->delete();

        return back()->with('success', 'Anggota dihapus. Kuota akun bertambah satu.');
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
}
