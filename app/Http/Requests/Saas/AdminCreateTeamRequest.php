<?php

namespace App\Http\Requests\Saas;

/**
 * Superadmin mendaftarkan tim langsung: data sama seperti form publik,
 * tanpa centang persetujuan, wajib bukti transfer, dan langsung aktif.
 */
class AdminCreateTeamRequest extends StoreTeamRegistrationRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSuperadmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['agree']);

        return $rules + [
            'proof' => ['required', 'file', 'mimes:png,jpg,jpeg,pdf', 'max:5120'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'proof.required' => 'Tempel (Ctrl+V) atau unggah bukti transfer terlebih dahulu.',
            'proof.mimes' => 'Bukti transfer harus berupa PNG, JPG, JPEG, atau PDF.',
            'proof.max' => 'Ukuran bukti transfer maksimal 5 MB.',
        ];
    }
}
