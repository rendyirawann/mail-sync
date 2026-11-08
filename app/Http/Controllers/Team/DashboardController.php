<?php

namespace App\Http\Controllers\Team;

use App\Enums\DispositionStatus;
use App\Enums\IncomingStatus;
use App\Enums\OutgoingStatus;
use App\Http\Controllers\Controller;
use App\Models\Disposition;
use App\Models\Employee;
use App\Models\IncomingLetter;
use App\Models\LetterCategory;
use App\Models\OutgoingLetter;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard tim: ringkasan angka, pekerjaan yang menunggu user ini,
 * surat terbaru, tren 6 bulan, dan checklist awal untuk tim baru.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, Team $team): View
    {
        $user = $request->user();
        $monthStart = now()->startOfMonth();

        $myDispositions = Disposition::query()
            ->with(['letter:id,subject,sender,agenda_number,agenda_year', 'sender:id,name'])
            ->whereHas('recipient', fn ($q) => $q->where('user_id', $user->id))
            ->where('status', '!=', DispositionStatus::Selesai->value)
            ->orderByRaw('due_date is null')->orderBy('due_date')
            ->limit(5)->get();

        $awaitingApproval = $user->can('team.outgoing.approve')
            ? OutgoingLetter::where('status', OutgoingStatus::Menunggu->value)->latest()->limit(5)->get()
            : collect();

        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $trend[] = [
                'label' => $month->locale('id')->translatedFormat('M'),
                'in' => IncomingLetter::whereBetween('received_date', [$month, $month->copy()->endOfMonth()])->count(),
                'out' => OutgoingLetter::whereBetween('letter_date', [$month, $month->copy()->endOfMonth()])->count(),
            ];
        }

        return view('team.dashboard', [
            'stats' => [
                'incoming_month' => IncomingLetter::where('received_date', '>=', $monthStart)->count(),
                'outgoing_month' => OutgoingLetter::where('letter_date', '>=', $monthStart)->count(),
                'incoming_new' => IncomingLetter::where('status', IncomingStatus::Baru->value)->count(),
                'dispositions_open' => Disposition::where('status', '!=', DispositionStatus::Selesai->value)->count(),
                'dispositions_overdue' => Disposition::where('status', '!=', DispositionStatus::Selesai->value)
                    ->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString())->count(),
            ],
            'myDispositions' => $myDispositions,
            'awaitingApproval' => $awaitingApproval,
            'recentIncoming' => IncomingLetter::latest()->limit(5)->get(),
            'trend' => $trend,
            'onboarding' => [
                ['done' => filled($team->profileValue('letterhead.name')), 'label' => 'Lengkapi profil & kop surat instansi', 'route' => 'team.settings.edit', 'ability' => 'team.settings.manage'],
                ['done' => Employee::exists(), 'label' => 'Tambah data pegawai & divisi (tujuan disposisi)', 'route' => 'team.employees.index', 'ability' => 'team.master.manage'],
                ['done' => LetterCategory::exists(), 'label' => 'Atur jenis & klasifikasi surat', 'route' => 'team.master.index', 'ability' => 'team.master.manage'],
                ['done' => $team->users()->count() > 1, 'label' => 'Undang anggota tim', 'route' => 'team.members.index', 'ability' => 'team.members.manage'],
                ['done' => IncomingLetter::exists(), 'label' => 'Catat surat masuk pertama', 'route' => 'team.incoming.create', 'ability' => 'team.letters.manage'],
            ],
        ]);
    }
}
