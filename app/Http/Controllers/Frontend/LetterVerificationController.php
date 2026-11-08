<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\OutgoingStatus;
use App\Http\Controllers\Controller;
use App\Models\OutgoingLetter;
use Illuminate\View\View;

/**
 * Halaman publik hasil pindai QR surat keluar: menunjukkan surat ini sah
 * tanpa membuka isi surat (hanya metadata minimal).
 */
class LetterVerificationController extends Controller
{
    public function __invoke(string $token): View
    {
        $letter = OutgoingLetter::withoutGlobalScopes()
            ->with(['team:id,name,slug', 'signer:id,name,position'])
            ->where('verification_token', $token)
            ->whereIn('status', [OutgoingStatus::Disetujui->value, OutgoingStatus::Terkirim->value])
            ->first();

        return view('frontend.verify', ['letter' => $letter, 'token' => $token]);
    }
}
