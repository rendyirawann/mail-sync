<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Arsip & pencarian gabungan surat masuk + keluar, dengan filter tahun.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request, Team $team): View
    {
        $q = trim((string) $request->query('q', ''));
        $year = (int) $request->query('tahun', 0);
        $kind = in_array($request->query('jenis'), ['masuk', 'keluar'], true) ? $request->query('jenis') : 'semua';
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';

        $incoming = collect();
        $outgoing = collect();

        if ($q !== '' || $year > 0) {
            if ($kind !== 'keluar') {
                $incoming = IncomingLetter::query()
                    ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('subject', 'like', $like)->orWhere('sender', 'like', $like)
                        ->orWhere('letter_number', 'like', $like)->orWhere('summary', 'like', $like)))
                    ->when($year > 0, fn ($query) => $query->where('agenda_year', $year))
                    ->latest('received_date')->limit(50)->get();
            }

            if ($kind !== 'masuk') {
                $outgoing = OutgoingLetter::query()
                    ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('subject', 'like', $like)->orWhere('recipient', 'like', $like)
                        ->orWhere('letter_number', 'like', $like)))
                    ->when($year > 0, fn ($query) => $query->whereYear('letter_date', $year))
                    ->latest('letter_date')->limit(50)->get();
            }
        }

        $years = IncomingLetter::query()->distinct()->orderByDesc('agenda_year')->pluck('agenda_year')
            ->merge([(int) now()->format('Y')])->unique()->sortDesc()->values();

        return view('team.archive.index', compact('q', 'year', 'kind', 'incoming', 'outgoing', 'years'));
    }
}
