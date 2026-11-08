<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Plan;
use App\Models\Setting;
use App\Support\ActivityRecorder;
use App\Support\SaasSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Superadmin: harga paket, rekening tujuan transfer, nomor WA admin,
 * dan template pesan WA — semuanya tampil otomatis di form pendaftaran.
 */
class BillingSettingController extends Controller
{
    public function index(): View
    {
        return view('backend.saas.billing.index', [
            'plans' => Plan::orderBy('price')->get(),
            'banks' => BankAccount::orderBy('sort_order')->orderBy('id')->get(),
            'whatsapp' => SaasSettings::whatsapp(),
            'waRegistration' => (string) Setting::get(SaasSettings::WA_REGISTRATION, SaasSettings::DEFAULT_REGISTRATION),
            'waRenewal' => (string) Setting::get(SaasSettings::WA_RENEWAL, SaasSettings::DEFAULT_RENEWAL),
        ]);
    }

    public function updatePlans(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plans' => ['required', 'array'],
            'plans.*.name' => ['required', 'string', 'max:60'],
            'plans.*.price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'plans.*.max_members' => ['required', 'integer', 'min:1', 'max:50'],
            'plans.*.description' => ['nullable', 'string', 'max:255'],
            'plans.*.is_active' => ['nullable', 'boolean'],
        ], [], ['plans.*.price' => 'harga', 'plans.*.name' => 'nama paket', 'plans.*.max_members' => 'maksimal akun']);

        foreach ($data['plans'] as $id => $values) {
            Plan::whereKey($id)->update([
                'name' => $values['name'],
                'price' => $values['price'],
                'max_members' => $values['max_members'],
                'description' => $values['description'] ?? null,
                'is_active' => (bool) ($values['is_active'] ?? false),
            ]);
        }

        ActivityRecorder::log('langganan', 'Memperbarui harga & paket langganan');

        return back()->with('success', 'Paket langganan diperbarui. Harga baru berlaku untuk pendaftaran/perpanjangan berikutnya.');
    }

    public function updateWhatsapp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'whatsapp' => ['required', 'string', 'regex:/^(\+?62|0)8[0-9]{7,12}$/'],
            'wa_registration' => ['required', 'string', 'max:1000'],
            'wa_renewal' => ['required', 'string', 'max:1000'],
        ], ['whatsapp.regex' => 'Nomor WhatsApp tidak valid. Contoh: 081234567890.'], ['whatsapp' => 'nomor WhatsApp']);

        $number = preg_replace('/\D+/', '', $data['whatsapp']) ?? '';
        Setting::set(SaasSettings::WHATSAPP, str_starts_with($number, '0') ? '62'.substr($number, 1) : $number);
        Setting::set(SaasSettings::WA_REGISTRATION, $data['wa_registration']);
        Setting::set(SaasSettings::WA_RENEWAL, $data['wa_renewal']);

        return back()->with('success', 'Kontak WhatsApp & template pesan disimpan.');
    }

    public function storeBank(Request $request): RedirectResponse
    {
        BankAccount::create($this->validateBank($request) + ['sort_order' => (int) BankAccount::max('sort_order') + 1]);

        return back()->with('success', 'Rekening ditambahkan.');
    }

    public function updateBank(Request $request, BankAccount $bank): RedirectResponse
    {
        $bank->update($this->validateBank($request));

        return back()->with('success', 'Rekening diperbarui.');
    }

    public function destroyBank(BankAccount $bank): RedirectResponse
    {
        $bank->delete();

        return back()->with('success', 'Rekening dihapus.');
    }

    /** @return array<string, mixed> */
    private function validateBank(Request $request): array
    {
        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:60'],
            'account_number' => ['required', 'string', 'max:40', 'regex:/^[0-9 \-]+$/'],
            'account_holder' => ['required', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ], ['account_number.regex' => 'Nomor rekening hanya boleh berisi angka.'], [
            'bank_name' => 'nama bank / e-wallet', 'account_number' => 'nomor rekening', 'account_holder' => 'atas nama',
        ]);

        return $data + ['is_active' => $request->boolean('is_active')];
    }
}
