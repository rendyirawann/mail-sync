<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Honorarium;
use App\Models\Team;
use App\Support\Tenancy\CurrentTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Modul opsional: monitoring honorarium. Nilai bersih dihitung otomatis dari
 * nilai bruto dan persentase pajak.
 */
class HonorariumController extends Controller
{
    public const STATUSES = ['belum_dibayar' => 'Belum dibayar', 'dibayar' => 'Dibayar'];

    public function index(Request $request, Team $team): View
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('bulan')) ? (string) $request->query('bulan') : now()->format('Y-m');

        $items = Honorarium::with('employee:id,name')
            ->whereDate('period', Carbon::parse($month.'-01')->toDateString())
            ->orderBy('activity')->get();

        return view('team.optional.honoraria', [
            'items' => $items,
            'month' => $month,
            'employees' => Employee::active()->get(['id', 'name']),
            'statuses' => self::STATUSES,
            'totalNet' => $items->sum('net_amount'),
        ]);
    }

    public function store(Request $request, Team $team): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'uuid', Rule::exists('employees', 'id')->where('team_id', app(CurrentTeam::class)->id())],
            'activity' => ['required', 'string', 'max:255'],
            'period' => ['required', 'date_format:Y-m'],
            'gross_amount' => ['required', 'integer', 'min:0', 'max:10000000000'],
            'tax_percent' => ['required', 'integer', 'min:0', 'max:50'],
            'attachment_url' => ['nullable', 'url:https', 'max:500'],
        ], [], ['employee_id' => 'pegawai', 'activity' => 'kegiatan', 'period' => 'periode', 'gross_amount' => 'nilai bruto', 'tax_percent' => 'pajak']);

        Honorarium::create([
            'employee_id' => $data['employee_id'],
            'activity' => $data['activity'],
            'period' => $data['period'].'-01',
            'gross_amount' => $data['gross_amount'],
            'tax_percent' => $data['tax_percent'],
            'net_amount' => (int) round($data['gross_amount'] * (100 - $data['tax_percent']) / 100),
            'attachment_url' => $data['attachment_url'] ?? null,
            'status' => 'belum_dibayar',
        ]);

        return back()->with('success', 'Honorarium dicatat.');
    }

    public function update(Request $request, Team $team, Honorarium $honorarium): RedirectResponse
    {
        $honorarium->update($request->validate(['status' => ['required', Rule::in(array_keys(self::STATUSES))]]));

        return back()->with('success', 'Status honorarium diperbarui.');
    }

    public function destroy(Team $team, Honorarium $honorarium): RedirectResponse
    {
        $honorarium->delete();

        return back()->with('success', 'Data honorarium dihapus.');
    }
}
