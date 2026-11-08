<?php

namespace App\Http\Middleware;

use App\Enums\TeamStatus;
use App\Models\Team;
use App\Support\Tenancy\CurrentTeam;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menetapkan tim aktif dari segmen URL /{team} dan membagikannya ke view.
 *
 * PENTING: middleware ini diprioritaskan SEBELUM SubstituteBindings
 * (bootstrap/app.php). Dengan begitu, saat Laravel me-resolve {incoming},
 * {outgoing}, dst., global scope BelongsToTeam sudah aktif sehingga model
 * milik tim lain tidak pernah bisa di-bind (mencegah IDOR lintas tenant).
 */
class ResolveTeam
{
    public function __construct(private readonly CurrentTeam $current) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $value = $route?->parameter('team');

        $team = $value instanceof Team
            ? $value
            : (is_string($value) ? Team::query()->where('slug', strtolower($value))->first() : null);

        if ($team === null) {
            abort(404);
        }

        if ($team->status === TeamStatus::Suspended) {
            abort(403, 'Akun tim ini sedang dinonaktifkan. Hubungi admin layanan.');
        }

        $route->setParameter('team', $team);
        $this->current->set($team);
        URL::defaults(['team' => $team->slug]);
        View::share('team', $team);

        return $next($request);
    }
}
