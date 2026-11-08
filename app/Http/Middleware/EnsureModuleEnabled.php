<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\CurrentTeam;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Modul opsional (lembur, perjadin, honorarium) hanya bisa diakses bila
 * dibuka owner di Pengaturan Tim. Contoh: ->middleware('team.module:lembur').
 */
class EnsureModuleEnabled
{
    public function __construct(private readonly CurrentTeam $current) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (! $this->current->get()?->moduleEnabled($module)) {
            abort(403, 'Modul ini belum diaktifkan. Owner tim dapat mengaktifkannya di Pengaturan Tim.');
        }

        return $next($request);
    }
}
