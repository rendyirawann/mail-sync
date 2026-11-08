<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Support\ActivityRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Profil & password akun sendiri (anggota tim).
 */
class ProfileController extends Controller
{
    public function edit(Team $team): View
    {
        return view('team.profile.edit', ['user' => request()->user()]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $user = $request->user();

        if ($request->input('section') === 'password') {
            $data = $request->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', 'confirmed', Password::defaults(), 'different:current_password'],
            ], ['password.different' => 'Password baru harus berbeda dari password lama.'], [
                'current_password' => 'password saat ini', 'password' => 'password baru',
            ]);

            $user->forceFill(['password' => $data['password']])->save();
            // Keluarkan sesi lain yang mungkin dipakai orang lain.
            Auth::logoutOtherDevices($data['password']);
            ActivityRecorder::log('profil', 'Mengganti password');

            return back()->with('success', 'Password diganti. Sesi di perangkat lain sudah dikeluarkan.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'no_wa' => ['nullable', 'string', 'regex:/^(\+?62|0)8[0-9]{7,12}$/'],
        ], ['no_wa.regex' => 'Nomor WhatsApp tidak valid.'], ['name' => 'nama', 'no_wa' => 'nomor WhatsApp']);

        $user->update($data);

        return back()->with('success', 'Profil diperbarui.');
    }
}
