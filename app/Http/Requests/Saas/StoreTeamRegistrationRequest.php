<?php

namespace App\Http\Requests\Saas;

use App\Models\Registration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * Validasi form pendaftaran tim (publik & oleh Superadmin).
 */
class StoreTeamRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'team_name' => ['required', 'string', 'min:3', 'max:120'],
            'owner_name' => ['required', 'string', 'min:3', 'max:100'],
            'owner_email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'owner_whatsapp' => ['required', 'string', 'regex:/^(\+?62|0)8[0-9]{7,12}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')->where('is_active', true)],
            'agree' => ['accepted'],
        ];
    }

    /** Satu email tidak boleh punya dua pendaftaran yang masih menunggu. */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $pending = Registration::query()
                    ->where('owner_email', strtolower((string) $this->input('owner_email')))
                    ->where('status', 'pending')
                    ->exists();

                if ($pending) {
                    $validator->errors()->add('owner_email', 'Email ini sudah memiliki pendaftaran yang menunggu konfirmasi pembayaran.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'team_name' => 'nama tim / instansi',
            'owner_name' => 'nama owner',
            'owner_email' => 'email owner',
            'owner_whatsapp' => 'nomor WhatsApp',
            'plan_id' => 'paket',
            'agree' => 'persetujuan syarat layanan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'owner_email.unique' => 'Email ini sudah dipakai akun lain. Gunakan email berbeda.',
            'owner_whatsapp.regex' => 'Nomor WhatsApp tidak valid. Contoh: 081234567890.',
            'agree.accepted' => 'Anda perlu menyetujui syarat layanan.',
        ];
    }
}
