<?php

namespace App\Support;

use App\Models\Registration;
use App\Models\Setting;
use App\Models\Team;

/**
 * Pengaturan layanan SaaS yang dikelola Superadmin (nomor WA, template pesan).
 * Disimpan di tabel settings agar bisa diubah tanpa deploy.
 */
class SaasSettings
{
    public const WHATSAPP = 'saas_whatsapp';

    public const WA_REGISTRATION = 'saas_wa_registration_template';

    public const WA_RENEWAL = 'saas_wa_renewal_template';

    public const DEFAULT_REGISTRATION = "Halo Admin {app}, saya ingin konfirmasi pembayaran pendaftaran.\n\nKode: {kode}\nTim/Instansi: {tim}\nNama owner: {owner}\nPaket: {paket}\nTotal: {total}\n\nBukti transfer saya lampirkan di chat ini. Terima kasih.";

    public const DEFAULT_RENEWAL = "Halo Admin {app}, saya ingin memperpanjang langganan.\n\nTim: {tim} ({slug})\nPaket: {paket}\nTotal: {total}\n\nBukti transfer saya lampirkan di chat ini. Terima kasih.";

    public static function whatsapp(): string
    {
        return (string) Setting::get(self::WHATSAPP, '');
    }

    /** Tautan wa.me berisi pesan konfirmasi pendaftaran, atau null bila nomor admin belum diatur. */
    public static function registrationLink(Registration $registration): ?string
    {
        return self::link(self::fill((string) Setting::get(self::WA_REGISTRATION, self::DEFAULT_REGISTRATION), [
            '{kode}' => $registration->code,
            '{tim}' => $registration->team_name,
            '{owner}' => $registration->owner_name,
            '{paket}' => $registration->plan->name,
            '{total}' => $registration->amountLabel(),
        ]));
    }

    public static function renewalLink(Team $team, \App\Models\Plan $plan): ?string
    {
        return self::link(self::fill((string) Setting::get(self::WA_RENEWAL, self::DEFAULT_RENEWAL), [
            '{tim}' => $team->name,
            '{slug}' => $team->slug,
            '{paket}' => $plan->name,
            '{total}' => $plan->priceLabel(),
        ]));
    }

    /** Tautan wa.me ke nomor pelanggan (dipakai Superadmin untuk kirim info login). */
    public static function customerLink(string $number, string $message): string
    {
        return 'https://wa.me/'.preg_replace('/\D+/', '', $number).'?text='.rawurlencode($message);
    }

    /** @param array<string, string> $vars */
    private static function fill(string $template, array $vars): string
    {
        return strtr($template, $vars + ['{app}' => (string) config('app.name')]);
    }

    private static function link(string $message): ?string
    {
        $number = preg_replace('/\D+/', '', self::whatsapp());

        return $number ? 'https://wa.me/'.$number.'?text='.rawurlencode($message) : null;
    }
}
