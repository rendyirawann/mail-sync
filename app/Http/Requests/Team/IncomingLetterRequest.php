<?php

namespace App\Http\Requests\Team;

use App\Enums\LetterNature;
use App\Support\Tenancy\CurrentTeam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi tambah/ubah surat masuk. Lampiran berupa tautan (Google Drive),
 * bukan upload, agar penyimpanan server tidak penuh.
 */
class IncomingLetterRequest extends FormRequest
{
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

        return [
            'letter_number' => ['required', 'string', 'max:100'],
            'letter_date' => ['required', 'date', 'before_or_equal:today'],
            'received_date' => ['required', 'date', 'after_or_equal:letter_date', 'before_or_equal:today'],
            'sender' => ['required', 'string', 'max:150'],
            'subject' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'nature' => ['required', Rule::enum(LetterNature::class)],
            'category_id' => ['nullable', 'integer', Rule::exists('letter_categories', 'id')->where('team_id', $teamId)],
            'classification_id' => ['nullable', 'integer', Rule::exists('letter_classifications', 'id')->where('team_id', $teamId)],
            'attachment_url' => ['nullable', 'url:https', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'letter_number' => 'nomor surat',
            'letter_date' => 'tanggal surat',
            'received_date' => 'tanggal diterima',
            'sender' => 'pengirim',
            'subject' => 'perihal',
            'summary' => 'ringkasan isi',
            'nature' => 'sifat surat',
            'category_id' => 'jenis surat',
            'classification_id' => 'klasifikasi',
            'attachment_url' => 'tautan berkas',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachment_url.url' => 'Tautan berkas harus diawali https:// (mis. tautan Google Drive).',
            'received_date.after_or_equal' => 'Tanggal diterima tidak boleh sebelum tanggal surat.',
        ];
    }
}
