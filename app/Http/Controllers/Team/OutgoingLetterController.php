<?php

namespace App\Http\Controllers\Team;

use App\Enums\LetterNature;
use App\Enums\OutgoingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\OutgoingLetterRequest;
use App\Models\Employee;
use App\Models\LetterCategory;
use App\Models\LetterClassification;
use App\Models\LetterTemplate;
use App\Models\OutgoingLetter;
use App\Models\Team;
use App\Services\Letters\LetterRenderer;
use App\Services\Letters\NumberingService;
use App\Services\Letters\OutgoingLetterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Surat keluar: pilih template → isi dengan pratinjau langsung → ajukan →
 * disetujui (nomor + QR) → unduh PDF → tandai terkirim.
 */
class OutgoingLetterController extends Controller
{
    public function __construct(
        private readonly OutgoingLetterService $service,
        private readonly LetterRenderer $renderer,
        private readonly NumberingService $numbering,
    ) {}

    public function index(Request $request, Team $team): View
    {
        $status = OutgoingStatus::tryFrom((string) $request->query('status', ''));
        $q = trim((string) $request->query('q', ''));

        $letters = OutgoingLetter::query()
            ->with(['creator:id,name', 'template:id,name'])
            ->when($status, fn ($query) => $query->where('status', $status->value))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
                $query->where(fn ($w) => $w->where('subject', 'like', $like)->orWhere('recipient', 'like', $like)->orWhere('letter_number', 'like', $like));
            })
            ->latest('letter_date')->latest()
            ->paginate(15)->withQueryString();

        return view('team.outgoing.index', [
            'letters' => $letters,
            'status' => $status?->value,
            'search' => $q,
            'counts' => OutgoingLetter::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    /** Langkah 1: pilih template. Langkah 2 (dengan ?template=): isi + pratinjau. */
    public function create(Request $request, Team $team): View
    {
        $templateId = (string) $request->query('template', '');

        if ($templateId === '') {
            return view('team.outgoing.choose', ['templates' => LetterTemplate::available()->get()]);
        }

        $template = $templateId === 'kosong' ? null : LetterTemplate::available()->findOrFail($templateId);

        $letter = new OutgoingLetter([
            'letter_date' => now(),
            'nature' => LetterNature::Biasa,
            'template_id' => $template?->id,
            'content' => $this->renderer->withDefaults($template, []),
        ]);

        return view('team.outgoing.form', $this->formData($team, $letter, $template));
    }

    public function store(OutgoingLetterRequest $request, Team $team): RedirectResponse
    {
        $letter = $this->service->save(null, $request->user(), $request->letterData());

        return $this->afterSave($request, $team, $letter);
    }

    public function show(Team $team, OutgoingLetter $outgoing): View
    {
        $outgoing->load(['template', 'signer', 'creator:id,name', 'approver:id,name', 'category', 'classification']);

        return view('team.outgoing.show', [
            'letter' => $outgoing,
            'paper' => $this->renderer->viewData($team, $outgoing, $outgoing->template, $outgoing->content ?? [], $outgoing->signer),
        ]);
    }

    public function edit(Team $team, OutgoingLetter $outgoing): View|RedirectResponse
    {
        if (! $outgoing->isEditable()) {
            return redirect()->route('team.outgoing.show', $outgoing)->with('warning', 'Surat yang sudah diajukan/disetujui tidak dapat diubah.');
        }

        return view('team.outgoing.form', $this->formData($team, $outgoing, $outgoing->template));
    }

    public function update(OutgoingLetterRequest $request, Team $team, OutgoingLetter $outgoing): RedirectResponse
    {
        $letter = $this->service->save($outgoing, $request->user(), $request->letterData());

        return $this->afterSave($request, $team, $letter);
    }

    public function submit(Request $request, Team $team, OutgoingLetter $outgoing): RedirectResponse
    {
        $this->service->submit($team, $outgoing, $request->user());

        return back()->with('success', 'Surat diajukan. Pimpinan akan menerima notifikasi untuk menyetujui.');
    }

    public function approve(Request $request, Team $team, OutgoingLetter $outgoing): RedirectResponse
    {
        $data = $request->validate(['letter_number' => ['nullable', 'string', 'max:100']], [], ['letter_number' => 'nomor surat']);
        $this->service->approve($team, $outgoing, $request->user(), $data['letter_number'] ?? null);

        return redirect()->route('team.outgoing.show', $outgoing)
            ->with('success', "Surat disetujui dengan nomor {$outgoing->fresh()?->letter_number}. PDF siap diunduh.");
    }

    public function reject(Request $request, Team $team, OutgoingLetter $outgoing): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:255']], [], ['note' => 'catatan revisi']);
        $this->service->reject($outgoing, $request->user(), $data['note']);

        return back()->with('success', 'Surat dikembalikan ke pembuat untuk direvisi.');
    }

    public function markSent(Team $team, OutgoingLetter $outgoing): RedirectResponse
    {
        $this->service->markSent($outgoing);

        return back()->with('success', 'Surat ditandai terkirim.');
    }

    public function pdf(Team $team, OutgoingLetter $outgoing): Response
    {
        $outgoing->load(['template', 'signer']);
        $name = Str::slug(($outgoing->letter_number ?: 'draf').'-'.$outgoing->subject, '-');

        return $this->renderer
            ->pdf($team, $outgoing, $outgoing->template, $outgoing->content ?? [], $outgoing->signer)
            ->stream(Str::limit($name, 80, '').'.pdf');
    }

    public function destroy(Team $team, OutgoingLetter $outgoing): RedirectResponse
    {
        $this->service->delete($outgoing);

        return redirect()->route('team.outgoing.index')->with('success', 'Surat keluar dihapus.');
    }

    private function afterSave(Request $request, Team $team, OutgoingLetter $letter): RedirectResponse
    {
        return match ($request->input('next')) {
            'submit' => tap(redirect()->route('team.outgoing.show', $letter), fn () => $this->service->submit($team, $letter, $request->user()))
                ->with('success', 'Draf disimpan dan diajukan untuk persetujuan.'),
            'approve' => $request->user()->can('team.outgoing.approve')
                ? tap(redirect()->route('team.outgoing.show', $letter), fn () => $this->service->approve($team, $letter, $request->user()))
                    ->with('success', 'Surat disimpan & disetujui. Nomor dan QR verifikasi sudah terbit.')
                : redirect()->route('team.outgoing.show', $letter)->with('warning', 'Draf disimpan. Anda tidak berwenang menyetujui surat.'),
            default => redirect()->route('team.outgoing.edit', $letter)->with('success', 'Draf disimpan.'),
        };
    }

    /** @return array<string, mixed> */
    private function formData(Team $team, OutgoingLetter $letter, ?LetterTemplate $template): array
    {
        $employees = Employee::active()->with('division:id,name')->get();
        $signer = $letter->signer_employee_id ? $employees->firstWhere('id', $letter->signer_employee_id) : null;
        $classifications = LetterClassification::orderBy('code')->get(['id', 'code', 'name']);

        return [
            'letter' => $letter,
            'template' => $template,
            'employees' => $employees,
            'categories' => LetterCategory::orderBy('name')->get(['id', 'name']),
            'classifications' => $classifications,
            'paper' => $this->renderer->viewData($team, $letter, $template, $letter->content ?? [], $signer),
            'numberPreview' => $this->numbering->peekOutgoing($team, $letter->letter_date ?? now(), null),
        ];
    }
}
