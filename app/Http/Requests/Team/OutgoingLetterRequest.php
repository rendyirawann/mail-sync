<?php

namespace App\Http\Requests\Team;

use App\Enums\LetterNature;
use App\Models\LetterTemplate;
use App\Support\Tenancy\CurrentTeam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi surat keluar. Isian template divalidasi dinamis dari definisi
 * field template yang dipilih (wajib/tidak, panjang maksimum).
 */
class OutgoingLetterRequest extends FormRequest
{
    private ?LetterTemplate $template = null;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('team.letters.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $teamId = app(CurrentTeam::class)->id();

        $rules = [
            'template_id' => ['nullable', 'uuid'],
            'recipient' => ['required', 'string', 'max:150'],
            'subject' => ['required', 'string', 'max:255'],
            'letter_date' => ['required', 'date'],
            'nature' => ['required', Rule::enum(LetterNature::class)],
            'category_id' => ['nullable', 'integer', Rule::exists('letter_categories', 'id')->where('team_id', $teamId)],
            'classification_id' => ['nullable', 'integer', Rule::exists('letter_classifications', 'id')->where('team_id', $teamId)],
            'signer_employee_id' => ['nullable', 'uuid', Rule::exists('employees', 'id')->where('team_id', $teamId)],
            'attachment_url' => ['nullable', 'url:https', 'max:500'],
            'content' => ['array'],
        ];

        $template = $this->template();

        if ($template === null) {
            $rules['content.isi'] = ['required', 'string', 'max:20000'];
        } else {
            foreach ($template->fields as $field) {
                $rules['content.'.$field['key']] = [($field['required'] ?? false) ? 'required' : 'nullable', 'string', 'max:'.(($field['type'] ?? 'text') === 'textarea' ? 10000 : 500)];
            }
        }

        return $rules;
    }

    /** Template yang dipilih — hanya milik tim ini atau bawaan. */
    public function template(): ?LetterTemplate
    {
        $id = $this->input('template_id');

        if (! is_string($id) || $id === '') {
            return null;
        }

        return $this->template ??= LetterTemplate::available()->find($id);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [
            'recipient' => 'tujuan / penerima',
            'subject' => 'perihal',
            'letter_date' => 'tanggal surat',
            'nature' => 'sifat surat',
            'signer_employee_id' => 'penanda tangan',
            'attachment_url' => 'tautan lampiran',
            'content.isi' => 'isi surat',
        ];

        foreach ($this->template()->fields ?? [] as $field) {
            $attributes['content.'.$field['key']] = strtolower($field['label']);
        }

        return $attributes;
    }

    /**
     * Data yang disimpan ke model (isian template dibersihkan dari key asing).
     *
     * @return array<string, mixed>
     */
    public function letterData(): array
    {
        $data = $this->safe()->except(['content']);
        $allowed = $this->template() ? array_column($this->template()->fields, 'key') : ['isi'];

        $data['template_id'] = $this->template()?->id;
        $data['content'] = array_intersect_key((array) $this->validated('content', []), array_flip($allowed));

        return $data;
    }
}
