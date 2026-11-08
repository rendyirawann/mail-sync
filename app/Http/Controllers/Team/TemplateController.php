<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\LetterTemplate;
use App\Models\OutgoingLetter;
use App\Models\Team;
use App\Services\Letters\LetterRenderer;
use App\Support\ActivityRecorder;
use App\Support\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Template surat keluar milik tim (+ melihat/menyalin template bawaan).
 * Isi HTML disanitasi sebelum disimpan (cegah stored XSS).
 */
class TemplateController extends Controller
{
    public function __construct(private readonly LetterRenderer $renderer) {}

    public function index(Team $team): View
    {
        return view('team.templates.index', [
            'templates' => LetterTemplate::withoutGlobalScope('team')
                ->where(fn ($q) => $q->whereNull('team_id')->orWhere('team_id', $team->id))
                ->orderByRaw('team_id is null')->orderBy('name')->get(),
        ]);
    }

    public function create(Team $team): View
    {
        return view('team.templates.form', [
            'template' => new LetterTemplate([
                'paper' => 'A4',
                'body' => "<p>Dengan hormat,</p>\n<p>{{isi}}</p>\n<p>Demikian disampaikan, atas perhatiannya diucapkan terima kasih.</p>",
                'fields' => [['key' => 'isi', 'label' => 'Isi surat', 'type' => 'textarea', 'required' => true]],
            ]),
        ]);
    }

    public function store(Request $request, Team $team): RedirectResponse
    {
        $template = LetterTemplate::create($this->validated($request) + ['is_active' => true]);
        ActivityRecorder::log('template surat', "Membuat template: {$template->name}", [], $template);

        return redirect()->route('team.templates.edit', $template)->with('success', 'Template disimpan. Coba gunakan saat membuat surat keluar.');
    }

    public function edit(Team $team, LetterTemplate $template): View|RedirectResponse
    {
        if ($template->isBuiltIn()) {
            return redirect()->route('team.templates.index')->with('warning', 'Template bawaan tidak dapat diubah — salin dulu menjadi template tim.');
        }

        return view('team.templates.form', ['template' => $template]);
    }

    public function update(Request $request, Team $team, LetterTemplate $template): RedirectResponse
    {
        abort_if($template->isBuiltIn(), 403, 'Template bawaan tidak dapat diubah.');

        $template->update($this->validated($request) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Template diperbarui.');
    }

    public function duplicate(Team $team, LetterTemplate $template): RedirectResponse
    {
        $copy = $template->replicate(['team_id']);
        $copy->team_id = $team->id;
        $copy->name = Str::limit($template->name.' (salinan)', 100, '');
        $copy->save();

        return redirect()->route('team.templates.edit', $copy)->with('success', 'Template disalin. Silakan sesuaikan.');
    }

    public function destroy(Team $team, LetterTemplate $template): RedirectResponse
    {
        abort_if($template->isBuiltIn(), 403, 'Template bawaan tidak dapat dihapus.');

        $template->delete();

        return redirect()->route('team.templates.index')->with('success', 'Template dihapus. Surat yang sudah dibuat tetap aman.');
    }

    /** Pratinjau template dengan contoh data (nama field sebagai isian). */
    public function preview(Team $team, LetterTemplate $template): View
    {
        $sample = [];
        foreach ($template->fields as $field) {
            $sample[$field['key']] = $field['default'] ?? '['.$field['label'].']';
        }

        $letter = new OutgoingLetter(['letter_date' => now(), 'recipient' => '[Tujuan surat]', 'subject' => '[Perihal]', 'nature' => 'biasa']);

        return view('team.templates.preview', [
            'template' => $template,
            'paper' => $this->renderer->viewData($team, $letter, $template, $sample, null),
        ]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'paper' => ['required', Rule::in(['A4', 'F4'])],
            'body' => ['required', 'string', 'max:50000'],
            'fields' => ['nullable', 'array', 'max:30'],
            'fields.*.key' => ['required', 'string', 'max:40', 'regex:/^[a-z][a-z0-9_]*$/', 'distinct', Rule::notIn(array_merge(array_keys(LetterRenderer::SYSTEM_FIELDS), ['qr']))],
            'fields.*.label' => ['required', 'string', 'max:80'],
            'fields.*.type' => ['required', Rule::in(['text', 'textarea', 'date'])],
            'fields.*.default' => ['nullable', 'string', 'max:2000'],
        ], [
            'fields.*.key.regex' => 'Kunci isian hanya huruf kecil, angka, dan garis bawah, diawali huruf (mis. tempat_acara).',
            'fields.*.key.not_in' => 'Kunci isian ":input" sudah dipakai sistem.',
            'fields.*.key.distinct' => 'Kunci isian tidak boleh dobel.',
        ], ['name' => 'nama template', 'body' => 'isi template', 'fields.*.key' => 'kunci isian', 'fields.*.label' => 'label isian']);

        $fields = [];
        foreach ($data['fields'] ?? [] as $i => $field) {
            $fields[] = [
                'key' => $field['key'],
                'label' => $field['label'],
                'type' => $field['type'],
                'required' => $request->boolean("fields.$i.required"),
            ] + (filled($field['default'] ?? null) ? ['default' => $field['default']] : []);
        }

        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'paper' => $data['paper'],
            'body' => HtmlSanitizer::clean($data['body']),
            'fields' => $fields,
        ];
    }
}
