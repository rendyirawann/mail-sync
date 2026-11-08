<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\CurrentTeam;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tim yang beku (masa aktif habis) tetap bisa melihat & mengekspor data,
 * tetapi tidak bisa menambah/mengubah/menghapus. Halaman tagihan dikecualikan
 * lewat route (tidak memakai middleware ini).
 */
class EnsureTeamWritable
{
    public function __construct(private readonly CurrentTeam $current) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $team = $this->current->get();

        if ($team === null || $request->isMethodSafe() || $team->isWritable()) {
            return $next($request);
        }

        $message = 'Masa aktif langganan tim sudah habis. Data hanya bisa dilihat — perpanjang langganan untuk menambah atau mengubah data.';

        if ($request->expectsJson()) {
            return response()->json(['status' => 'error', 'message' => $message], 402);
        }

        return redirect()->route('team.billing.index', $team)->with('warning', $message);
    }
}
