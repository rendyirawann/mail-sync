<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\Team;
use App\Support\Tenancy\CurrentTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Modul opsional: monitoring lembur. Jam dihitung otomatis dari jam mulai–selesai
 * (melewati tengah malam didukung).
 */
class OvertimeController extends Controller
{
    public const STATUSES = ['diajukan' => 'Diajukan', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'];

    public function index(Request $request, Team $team): View
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('bulan')) ? (string) $request->query('bulan') : now()->format('Y-m');
        $start = Carbon::parse($month.'-01');

        $items = Overtime::with('employee:id,name')
            ->whereBetween('date', [$start, $start->copy()->endOfMonth()])
            ->orderByDesc('date')->get();

        return view('team.optional.overtimes', [
            'items' => $items,
            'month' => $month,
            'employees' => Employee::active()->get(['id', 'name']),
            'statuses' => self::STATUSES,
            'totalHours' => $items->where('status', '!=', 'ditolak')->sum('hours'),
        ]);
    }

    public function store(Request $request, Team $team): RedirectResponse
    {
        Overtime::create($this->validated($request));

        return back()->with('success', 'Lembur dicatat.');
    }

    public function update(Request $request, Team $team, Overtime $overtime): RedirectResponse
    {
        $overtime->update($request->validate(['status' => ['required', Rule::in(array_keys(self::STATUSES))]]));

        return back()->with('success', 'Status lembur diperbarui.');
    }

    public function destroy(Team $team, Overtime $overtime): RedirectResponse
    {
        $overtime->delete();

        return back()->with('success', 'Data lembur dihapus.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'employee_id' => ['required', 'uuid', Rule::exists('employees', 'id')->where('team_id', app(CurrentTeam::class)->id())],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            'activity' => ['required', 'string', 'max:255'],
            'attachment_url' => ['nullable', 'url:https', 'max:500'],
        ], [], ['employee_id' => 'pegawai', 'date' => 'tanggal', 'start_time' => 'jam mulai', 'end_time' => 'jam selesai', 'activity' => 'kegiatan']);

        $start = Carbon::createFromFormat('H:i', $data['start_time']);
        $end = Carbon::createFromFormat('H:i', $data['end_time']);
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return $data + ['hours' => round($start->diffInMinutes($end) / 60, 2), 'status' => 'diajukan'];
    }
}
