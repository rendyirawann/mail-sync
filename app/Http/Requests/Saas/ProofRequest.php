<?php

namespace App\Http\Requests\Saas;

use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Konfirmasi pembayaran oleh Superadmin: bukti transfer (tempel screenshot
 * atau unggah PNG/JPG/JPEG/PDF) + catatan. Dipakai untuk menyetujui
 * pendaftaran maupun perpanjangan.
 */
class ProofRequest extends FormRequest
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
        return [
            'proof' => ['required', 'file', 'mimes:png,jpg,jpeg,pdf', 'max:5120'],
            'note' => ['nullable', 'string', 'max:255'],
            'plan_id' => [Rule::requiredIf($this->routeIs('admin.saas.teams.renew')), 'integer', Rule::exists(Plan::class, 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['proof' => 'bukti transfer', 'note' => 'catatan', 'plan_id' => 'paket'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'proof.required' => 'Tempel (Ctrl+V) atau unggah bukti transfer terlebih dahulu.',
            'proof.mimes' => 'Bukti transfer harus berupa PNG, JPG, JPEG, atau PDF.',
            'proof.max' => 'Ukuran bukti transfer maksimal 5 MB.',
        ];
    }
}
