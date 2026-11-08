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
 * Semua model ber-trait BelongsToTeam otomatis terfilter ke tim ini.
 */
class ResolveTeam
{
    public function __construct(private readonly CurrentTeam $current) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $team = $request->route('team');

        if (! $team instanceof Team) {
            abort(404);
        }

        if ($team->status === TeamStatus::Suspended) {
            abort(403, 'Akun tim ini sedang dinonaktifkan. Hubungi admin layanan.');
        }

        $this->current->set($team);
        URL::defaults(['team' => $team->slug]);
        View::share('team', $team);

        return $next($request);
    }
}
