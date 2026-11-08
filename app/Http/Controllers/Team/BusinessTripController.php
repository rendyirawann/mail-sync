<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\BusinessTrip;
use App\Models\Employee;
use App\Models\OutgoingLetter;
use App\Models\Team;
use App\Support\Tenancy\CurrentTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Modul opsional: monitoring perjalanan dinas, bisa ditautkan ke surat tugas
 * (surat keluar) yang sudah dibuat.
 */
class BusinessTripController extends Controller
{
    public const STATUSES = ['direncanakan' => 'Direncanakan', 'berlangsung' => 'Berlangsung', 'selesai' => 'Selesai', 'batal' => 'Batal'];

    public function index(Request $request, Team $team): View
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('bulan')) ? (string) $request->query('bulan') : now()->format('Y-m');
        $start = Carbon::parse($month.'-01');

        $items = BusinessTrip::with(['employee:id,name', 'assignmentLetter:id,letter_number,subject'])
            ->where('start_date', '<=', $start->copy()->endOfMonth())
            ->where('end_date', '>=', $start)
            ->orderBy('start_date')->get();

        return view('team.optional.trips', [
            'items' => $items,
            'month' => $month,
            'employees' => Employee::active()->get(['id', 'name']),
            'letters' => OutgoingLetter::whereNotNull('letter_number')->latest('letter_date')->limit(100)->get(['id', 'letter_number', 'subject']),
            'statuses' => self::STATUSES,
            'totalCost' => $items->where('status', '!=', 'batal')->sum('cost'),
        ]);
    }

    public function store(Request $request, Team $team): RedirectResponse
    {
        BusinessTrip::create($this->validated($request) + ['status' => 'direncanakan']);

        return back()->with('success', 'Perjalanan dinas dicatat.');
    }

    public function update(Request $request, Team $team, BusinessTrip $trip): RedirectResponse
    {
        $trip->update($request->validate(['status' => ['required', Rule::in(array_keys(self::STATUSES))]]));

        return back()->with('success', 'Status perjalanan dinas diperbarui.');
    }

    public function destroy(Team $team, BusinessTrip $trip): RedirectResponse
    {
        $trip->delete();

        return back()->with('success', 'Data perjalanan dinas dihapus.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $teamId = app(CurrentTeam::class)->id();

        return $request->validate([
            'employee_id' => ['required', 'uuid', Rule::exists('employees', 'id')->where('team_id', $teamId)],
            'outgoing_letter_id' => ['nullable', 'uuid', Rule::exists('outgoing_letters', 'id')->where('team_id', $teamId)],
            'destination' => ['required', 'string', 'max:150'],
            'purpose' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'cost' => ['nullable', 'integer', 'min:0', 'max:10000000000'],
            'attachment_url' => ['nullable', 'url:https', 'max:500'],
        ], [], ['employee_id' => 'pegawai', 'outgoing_letter_id' => 'surat tugas', 'destination' => 'tujuan', 'purpose' => 'keperluan',
            'start_date' => 'tanggal berangkat', 'end_date' => 'tanggal kembali', 'cost' => 'biaya']) + ['cost' => 0];
    }
}
