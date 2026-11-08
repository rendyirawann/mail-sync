<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Team;
use App\Support\ActivityRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Login/logout area tim di /{slug}/login. LoginRequest sudah membatasi
 * kredensial ke team_id tim ini, jadi akun tim lain/Superadmin tidak bisa masuk.
 */
class AuthController extends Controller
{
    public function create(): View
    {
        return view('team.auth.login');
    }

    public function store(LoginRequest $request, Team $team): JsonResponse|RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();

        if ($user->is_active === false || $user->banned_at !== null) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages(['email' => 'Akun Anda dinonaktifkan oleh owner tim.']);
        }

        $user->forceFill(['last_ip' => $request->ip(), 'last_login' => now()])->save();
        ActivityRecorder::loggedIn();

        $target = route('team.dashboard', $team);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Login berhasil, mengalihkan...', 'redirect' => $target]);
        }

        return redirect()->intended($target);
    }

    public function destroy(Request $request, Team $team): RedirectResponse
    {
        ActivityRecorder::loggedOut();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('team.login', $team);
    }
}
