<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panel /admin hanya untuk akun platform (Superadmin & staf platform).
 * Anggota tim yang kebetulan masih login diarahkan ke dashboard timnya.
 */
class EnsurePlatformUser
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->team_id !== null) {
            $team = $user->team;

            if ($team !== null) {
                return redirect()->route('team.dashboard', $team);
            }

            Auth::guard('web')->logout();

            abort(403);
        }

        return $next($request);
    }
}
