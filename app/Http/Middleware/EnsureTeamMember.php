<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\CurrentTeam;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hanya anggota tim aktif di URL ini yang boleh lewat. User yang login di
 * tim lain (atau Superadmin) diarahkan ke halaman login tim ini.
 */
class EnsureTeamMember
{
    public function __construct(private readonly CurrentTeam $current) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $team = $this->current->get();

        if ($user === null || $team === null || $user->team_id !== $team->getKey()) {
            if ($user !== null) {
                Log::channel('security')->warning('Akses lintas tim ditolak', [
                    'user_id' => $user->getKey(),
                    'team' => $team?->slug,
                    'ip' => $request->ip(),
                ]);
            }

            if ($request->expectsJson()) {
                abort(403, 'Anda bukan anggota tim ini.');
            }

            return redirect()->route('team.login', $team)
                ->with('error', 'Silakan masuk dengan akun tim '.$team?->name.'.');
        }

        if ($user->is_active === false || $user->banned_at !== null) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();

            return redirect()->route('team.login', $team)->with('error', 'Akun Anda dinonaktifkan oleh owner tim.');
        }

        return $next($request);
    }
}
