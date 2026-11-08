<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\LetterCategory;
use App\Models\LetterClassification;
use App\Models\Team;
use App\Support\Tenancy\CurrentTeam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Data master sederhana dalam satu halaman bertab: klasifikasi, jenis surat,
 * dan kontak instansi. Jenis data di-whitelist lewat konstanta TYPES.
 */
class MasterDataController extends Controller
{
    /** @var array<string, array{model: class-string<Model>, label: string}> */
    private const TYPES = [
        'klasifikasi' => ['model' => LetterClassification::class, 'label' => 'Klasifikasi'],
        'jenis' => ['model' => LetterCategory::class, 'label' => 'Jenis surat'],
        'kontak' => ['model' => Contact::class, 'label' => 'Kontak instansi'],
    ];

    /** Klasifikasi umum arsip (dapat langsung dipakai tim baru). */
    private const DEFAULT_CLASSIFICATIONS = [
        '000' => 'Umum', '005' => 'Undangan', '045' => 'Kearsipan', '090' => 'Perjalanan Dinas',
        '100' => 'Pemerintahan', '400' => 'Kesejahteraan Rakyat', '700' => 'Pengawasan', '800' => 'Kepegawaian', '900' => 'Keuangan',
    ];

    private const DEFAULT_CATEGORIES = ['Undangan', 'Pemberitahuan', 'Permohonan', 'Laporan', 'Surat Tugas', 'Surat Keterangan', 'Nota Dinas', 'Edaran'];

    public function index(Request $request, Team $team): View
    {
        $tab = array_key_exists((string) $request->query('tab'), self::TYPES) ? (string) $request->query('tab') : 'klasifikasi';

        return view('team.master.index', [
            'tab' => $tab,
            'types' => self::TYPES,
            'classifications' => LetterClassification::orderBy('code')->get(),
            'categories' => LetterCategory::orderBy('name')->get(),
            'contacts' => Contact::orderBy('name')->paginate(20, ['*'], 'halaman')->withQueryString(),
        ]);
    }

    public function store(Request $request, Team $team, string $type): RedirectResponse
    {
        $config = $this->config($type);

        if ($request->boolean('defaults')) {
            return $this->seedDefaults($type);
        }

        $config['model']::create($this->validated($request, $type));

        return redirect()->route('team.master.index', ['tab' => $type])->with('success', $config['label'].' ditambahkan.');
    }

    public function update(Request $request, Team $team, string $type, int $id): RedirectResponse
    {
        $config = $this->config($type);
        $config['model']::findOrFail($id)->update($this->validated($request, $type, $id));

        return redirect()->route('team.master.index', ['tab' => $type])->with('success', $config['label'].' diperbarui.');
    }

    public function destroy(Team $team, string $type, int $id): RedirectResponse
    {
        $config = $this->config($type);
        $config['model']::findOrFail($id)->delete();

        return redirect()->route('team.master.index', ['tab' => $type])->with('success', $config['label'].' dihapus. Surat terkait tetap tersimpan.');
    }

    /** @return array{model: class-string<Model>, label: string} */
    private function config(string $type): array
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        return self::TYPES[$type];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, string $type, ?int $ignore = null): array
    {
        $teamId = app(CurrentTeam::class)->id();

        return match ($type) {
            'klasifikasi' => $request->validate([
                'code' => ['required', 'string', 'max:30', Rule::unique('letter_classifications', 'code')->where('team_id', $teamId)->ignore($ignore)],
                'name' => ['required', 'string', 'max:150'],
            ], [], ['code' => 'kode', 'name' => 'nama klasifikasi']),
            'jenis' => $request->validate(['name' => ['required', 'string', 'max:100']], [], ['name' => 'nama jenis']),
            default => $request->validate([
                'name' => ['required', 'string', 'max:150'],
                'address' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:20'],
                'email' => ['nullable', 'email', 'max:255'],
            ], [], ['name' => 'nama instansi', 'address' => 'alamat', 'phone' => 'telepon']),
        };
    }

    private function seedDefaults(string $type): RedirectResponse
    {
        if ($type === 'klasifikasi') {
            foreach (self::DEFAULT_CLASSIFICATIONS as $code => $name) {
                LetterClassification::firstOrCreate(['code' => (string) $code], ['name' => $name]);
            }
        } elseif ($type === 'jenis') {
            foreach (self::DEFAULT_CATEGORIES as $name) {
                LetterCategory::firstOrCreate(['name' => $name]);
            }
        }

        return redirect()->route('team.master.index', ['tab' => $type])->with('success', 'Daftar bawaan ditambahkan. Silakan sesuaikan.');
    }
}
