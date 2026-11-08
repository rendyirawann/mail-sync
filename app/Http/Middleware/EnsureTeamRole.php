<?php

namespace App\Http\Middleware;

use App\Enums\TeamRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi route ke peran tim tertentu. Contoh: ->middleware('team.role:owner,admin').
 */
class EnsureTeamRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowed = array_map(fn (string $role) => TeamRole::from($role), $roles);

        if (! $request->user()?->hasTeamRole(...$allowed)) {
            abort(403, 'Fitur ini hanya untuk peran: '.implode(', ', array_map(fn (TeamRole $r) => $r->label(), $allowed)).'.');
        }

        return $next($request);
    }
}
