<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\Letters\NumberingService;
use App\Support\ActivityRecorder;
use App\Support\SafeRemoteImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengaturan tim: profil instansi, kop surat, format penomoran, modul opsional.
 */
class TeamSettingController extends Controller
{
    public function edit(Team $team, NumberingService $numbering): View
    {
        return view('team.settings.edit', [
            'numberExample' => $numbering->formatOutgoing($team, 7, now(), '005'),
            'modules' => Team::OPTIONAL_MODULES,
        ]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'letterhead_name' => ['required', 'string', 'max:150'],
            'letterhead_lines' => ['nullable', 'string', 'max:500'],
            'letterhead_logo_url' => ['nullable', 'url:https', 'max:500'],
            'outgoing_format' => ['required', 'string', 'max:100', 'regex:/\{nomor\}/'],
            'digits' => ['required', 'integer', 'min:1', 'max:6'],
            'abbreviation' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9.\-]*$/'],
        ], [
            'outgoing_format.regex' => 'Format nomor wajib memuat penanda {nomor}.',
            'letterhead_logo_url.url' => 'Tautan logo harus diawali https:// (mis. tautan Google Drive).',
        ], [
            'name' => 'nama tim', 'letterhead_name' => 'nama instansi di kop', 'letterhead_lines' => 'baris kop',
            'letterhead_logo_url' => 'tautan logo', 'outgoing_format' => 'format nomor surat', 'digits' => 'jumlah digit',
            'abbreviation' => 'singkatan instansi',
        ]);

        $logo = SafeRemoteImage::normalizeUrl($data['letterhead_logo_url'] ?? null);
        if ($logo !== null && ! SafeRemoteImage::isPublicHttpUrl($logo)) {
            return back()->withInput()->withErrors(['letterhead_logo_url' => 'Tautan logo tidak dapat diakses publik.']);
        }

        $team->forceFill([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'profile' => array_replace_recursive($team->profile ?? [], [
                'letterhead' => ['name' => $data['letterhead_name'], 'lines' => $data['letterhead_lines'] ?? '', 'logo_url' => $data['letterhead_logo_url'] ?? null],
                'numbering' => ['outgoing_format' => $data['outgoing_format'], 'digits' => (int) $data['digits'], 'abbreviation' => $data['abbreviation'] ?? null],
            ]),
        ])->save();

        ActivityRecorder::log('pengaturan tim', 'Memperbarui profil, kop surat, dan penomoran');

        return back()->with('success', 'Pengaturan tim disimpan.');
    }

    /** Owner membuka/mengunci modul opsional untuk timnya. */
    public function modules(Request $request, Team $team): RedirectResponse
    {
        $modules = [];
        foreach (array_keys(Team::OPTIONAL_MODULES) as $key) {
            $modules[$key] = $request->boolean("modules.$key");
        }

        $team->forceFill(['modules' => $modules])->save();
        $enabled = implode(', ', array_keys(array_filter($modules)));
        ActivityRecorder::log('pengaturan tim', 'Mengubah modul opsional: '.($enabled !== '' ? $enabled : 'semua dikunci'));

        return back()->with('success', 'Modul tim diperbarui. Menu menyesuaikan otomatis.');
    }
}
