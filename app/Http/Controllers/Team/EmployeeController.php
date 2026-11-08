<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Team;
use App\Support\Tenancy\CurrentTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pegawai & divisi. Pegawai tidak memakan kuota akun — dipakai sebagai tujuan
 * disposisi dan penanda tangan. Pegawai bisa ditautkan ke akun anggota agar
 * disposisinya muncul di "Disposisi Saya".
 */
class EmployeeController extends Controller
{
    public function index(Request $request, Team $team): View
    {
        $q = trim((string) $request->query('q', ''));
        $division = $request->query('divisi');

        return view('team.employees.index', [
            'employees' => Employee::query()->with(['division:id,name', 'user:id,name'])
                ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%')
                    ->orWhere('nip', 'like', '%'.$q.'%')->orWhere('position', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%')))
                ->when(is_numeric($division), fn ($query) => $query->where('division_id', (int) $division))
                ->orderByDesc('is_active')->orderBy('name')
                ->paginate(20)->withQueryString(),
            'divisions' => Division::withCount('employees')->orderBy('sort_order')->orderBy('name')->get(),
            'accounts' => $team->users()->orderBy('name')->get(['id', 'name', 'email']),
            'linkedUserIds' => Employee::whereNotNull('user_id')->pluck('user_id')->all(),
            'search' => $q,
            'divisionFilter' => $division,
        ]);
    }

    public function store(Request $request, Team $team): RedirectResponse
    {
        Employee::create($this->validated($request));

        return back()->with('success', 'Pegawai ditambahkan.');
    }

    public function update(Request $request, Team $team, Employee $employee): RedirectResponse
    {
        $employee->update($this->validated($request, $employee));

        return back()->with('success', 'Data pegawai diperbarui.');
    }

    public function destroy(Team $team, Employee $employee): RedirectResponse
    {
        if ($employee->dispositions()->exists()) {
            $employee->update(['is_active' => false]);

            return back()->with('success', 'Pegawai memiliki riwayat disposisi, jadi dinonaktifkan (tidak dihapus) agar riwayat tetap utuh.');
        }

        $employee->delete();

        return back()->with('success', 'Pegawai dihapus.');
    }

    public function storeDivision(Request $request, Team $team): RedirectResponse
    {
        Division::create($this->validatedDivision($request) + ['sort_order' => (int) Division::max('sort_order') + 1]);

        return back()->with('success', 'Divisi ditambahkan.');
    }

    public function updateDivision(Request $request, Team $team, Division $division): RedirectResponse
    {
        $division->update($this->validatedDivision($request));

        return back()->with('success', 'Divisi diperbarui.');
    }

    public function destroyDivision(Team $team, Division $division): RedirectResponse
    {
        $division->delete();

        return back()->with('success', 'Divisi dihapus. Pegawai di dalamnya kini tanpa divisi.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Employee $employee = null): array
    {
        $teamId = app(CurrentTeam::class)->id();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'nip' => ['nullable', 'string', 'max:30'],
            'position' => ['nullable', 'string', 'max:100'],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')->where('team_id', $teamId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'user_id' => ['nullable', 'uuid', Rule::exists('users', 'id')->where('team_id', $teamId),
                Rule::unique('employees', 'user_id')->where('team_id', $teamId)->ignore($employee?->id)],
        ], ['user_id.unique' => 'Akun ini sudah ditautkan ke pegawai lain.'], [
            'name' => 'nama', 'nip' => 'NIP', 'position' => 'jabatan', 'division_id' => 'divisi', 'user_id' => 'akun tertaut',
        ]);

        return $data + ['is_active' => $request->has('is_active') ? $request->boolean('is_active') : true];
    }

    /** @return array<string, mixed> */
    private function validatedDivision(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20'],
        ], [], ['name' => 'nama divisi', 'code' => 'kode divisi']);
    }
}
