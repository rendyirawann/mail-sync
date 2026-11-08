<?php

namespace App\Http\Controllers\Team;

use App\Enums\IncomingStatus;
use App\Enums\LetterNature;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\IncomingLetterRequest;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\IncomingLetter;
use App\Models\LetterCategory;
use App\Models\LetterClassification;
use App\Models\Team;
use App\Services\Letters\IncomingLetterService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Surat masuk: daftar + filter, catat, ubah, detail (dengan disposisi), status.
 * Catatan: method selalu menerima Team $team lebih dulu (parameter route /{team}).
 */
class IncomingLetterController extends Controller
{
    public function __construct(private readonly IncomingLetterService $service) {}

    public function index(Request $request, Team $team): View
    {
        $filters = $request->only(['q', 'status', 'nature', 'category_id', 'from', 'to']);

        $letters = $this->filtered($filters)
            ->with(['category:id,name'])
            ->withCount('dispositions')
            ->latest('received_date')
            ->latest('agenda_number')
            ->paginate(15)
            ->withQueryString();

        return view('team.incoming.index', [
            'letters' => $letters,
            'filters' => $filters,
            'categories' => LetterCategory::orderBy('name')->get(['id', 'name']),
            'counts' => IncomingLetter::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    /** Saran pengirim (autocomplete) dari kontak yang pernah dipakai. */
    public function data(Request $request, Team $team): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        return response()->json(
            Contact::query()
                ->when($q !== '', fn ($query) => $query->where('name', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%'))
                ->orderBy('name')->limit(10)->pluck('name')
        );
    }

    public function create(Team $team): View
    {
        return view('team.incoming.form', $this->formData(new IncomingLetter([
            'letter_date' => now(), 'received_date' => now(), 'nature' => LetterNature::Biasa,
        ])));
    }

    public function store(IncomingLetterRequest $request, Team $team): RedirectResponse
    {
        $letter = $this->service->create($team, $request->user(), $request->validated());

        $message = "Surat masuk tercatat dengan nomor agenda {$letter->agendaLabel()}.";

        return $request->boolean('add_another')
            ? redirect()->route('team.incoming.create')->with('success', $message)
            : redirect()->route('team.incoming.show', $letter)->with('success', $message.' Langkah berikutnya: buat disposisi.');
    }

    public function show(Team $team, IncomingLetter $incoming): View
    {
        $incoming->load([
            'category', 'classification', 'creator:id,name',
            'dispositions' => fn ($q) => $q->with(['recipient.division', 'sender:id,name'])->oldest(),
        ]);

        return view('team.incoming.show', [
            'letter' => $incoming,
            'employees' => Employee::active()->with('division:id,name')->get(),
        ]);
    }

    public function edit(Team $team, IncomingLetter $incoming): View
    {
        return view('team.incoming.form', $this->formData($incoming));
    }

    public function update(IncomingLetterRequest $request, Team $team, IncomingLetter $incoming): RedirectResponse
    {
        $this->service->update($incoming, $request->validated());

        return redirect()->route('team.incoming.show', $incoming)->with('success', 'Perubahan surat masuk disimpan.');
    }

    public function status(Request $request, Team $team, IncomingLetter $incoming): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::enum(IncomingStatus::class)]]);
        $this->service->changeStatus($incoming, IncomingStatus::from($data['status']));

        return back()->with('success', 'Status surat diperbarui menjadi '.IncomingStatus::from($data['status'])->label().'.');
    }

    public function destroy(Team $team, IncomingLetter $incoming): RedirectResponse
    {
        $this->service->delete($incoming);

        return redirect()->route('team.incoming.index')->with('success', 'Surat masuk dihapus.');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<IncomingLetter>
     */
    private function filtered(array $filters): Builder
    {
        $q = trim((string) ($filters['q'] ?? ''));

        return IncomingLetter::query()
            ->when($q !== '', function (Builder $query) use ($q) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
                $query->where(fn (Builder $w) => $w->where('subject', 'like', $like)
                    ->orWhere('sender', 'like', $like)
                    ->orWhere('letter_number', 'like', $like)
                    ->orWhere('summary', 'like', $like));
            })
            ->when(IncomingStatus::tryFrom((string) ($filters['status'] ?? '')), fn ($query, $s) => $query->where('status', $s->value))
            ->when(LetterNature::tryFrom((string) ($filters['nature'] ?? '')), fn ($query, $n) => $query->where('nature', $n->value))
            ->when(is_numeric($filters['category_id'] ?? null), fn ($query) => $query->where('category_id', (int) $filters['category_id']))
            ->when($this->date($filters['from'] ?? null), fn ($query, $d) => $query->whereDate('received_date', '>=', $d))
            ->when($this->date($filters['to'] ?? null), fn ($query, $d) => $query->whereDate('received_date', '<=', $d));
    }

    private function date(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    /** @return array<string, mixed> */
    private function formData(IncomingLetter $letter): array
    {
        return [
            'letter' => $letter,
            'categories' => LetterCategory::orderBy('name')->get(['id', 'name']),
            'classifications' => LetterClassification::orderBy('code')->get(['id', 'code', 'name']),
        ];
    }
}
