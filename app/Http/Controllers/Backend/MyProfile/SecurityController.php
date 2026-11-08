<?php

namespace App\Http\Controllers\Backend\MyProfile;

use App\Http\Controllers\Controller;
use App\Rules\MatchOldPassword;
use App\Support\ActivityRecorder;
use Auth;
use Carbon\Carbon;
use DB;
use Hash;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    public function index(Request $request)
    {
        $akun = Auth::user();

        return view('backend.my_profile.security.index', compact('akun'));
    }

    public function edit($id)
    {
        $user = $this->ownAccountOrFail($id);
        $html = view('backend.my_profile.security.edit', compact('user'))->render();

        return response()->json(['html' => $html]);
    }

    public function update(Request $request, $id)
    {
        $user = $this->ownAccountOrFail($id);
        $formattedTime = Carbon::now()->diffForHumans();

        $validator = \Validator::make($request->all(), [
            'email' => 'required|email|max:255|unique:users,email,'.$user->getKey(),
            'current_password' => ['required', new MatchOldPassword],
            'new_password' => ['required', \Illuminate\Validation\Rules\Password::defaults()],
            'new_confirm_password' => 'required|same:new_password',
        ], [
            'email.required' => 'Email wajib diisi',
            'current_password.required' => 'Password terakhir wajib diisi',
            'new_password.required' => 'Password baru wajib diisi',
            'new_password.min' => 'Password baru minimal harus 8 karakter',
            'new_confirm_password.required' => 'Konfirmasi password baru wajib diisi',
            'new_confirm_password.same' => 'Password baru dan konfirmasi tidak sama',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();

            // 📌 Simpan data lama untuk audit
            $old = $user->toArray();

            // 📌 Update email + password
            $user->email = $request->email;
            $user->password = Hash::make($request->new_password);
            $user->save();

            // 📌 Simpan data baru untuk audit
            $new = $user->toArray();

            // ===============================
            // LOG ACTIVITY (SECURITY AUDIT)
            // ===============================

            ActivityRecorder::updated('security', 'Mengganti Password Akun', $old, $new, $user);

            DB::commit();

            return response()->json([
                'success' => 'Password & Email akun berhasil diperbaharui.',
                'time' => $formattedTime,
                'judul' => 'Berhasil',
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'error' => 'Terjadi kesalahan di aplikasi, hubungi Developer.',
                'time' => $formattedTime,
                'judul' => 'Aplikasi Error',
                'errorMessage' => $this->debugMessage($e),
            ]);
        }
    }

    /**
     * Route lama `change.password` (POST /admin/my-security) — dulu menunjuk ke
     * method yang tidak ada (500). Sekarang memakai alur update untuk akun sendiri.
     */
    public function store(Request $request)
    {
        return $this->update($request, (string) Auth::id());
    }

    public function logoutOtherDevices(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ], [
            'password.required' => 'Password wajib diisi untuk konfirmasi.',
            'password.current_password' => 'Password yang Anda masukkan salah.',
        ]);

        Auth::logoutOtherDevices($request->password);

        return back()->with('success', 'Berhasil logout dari semua perangkat lain.');
    }
}
