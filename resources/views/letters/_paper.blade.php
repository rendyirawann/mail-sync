{{--
    Isi kertas surat (kop + isi + tanda tangan + QR). Dipakai bersama oleh:
      - letters/print.blade.php   → PDF (dompdf)
      - halaman isi surat keluar   → pratinjau langsung (JS memperbarui <span data-pv>)
    Variabel: $letterhead, $body, $letter, $qr, $paper.
    Hanya CSS yang didukung dompdf (tanpa flex/grid).
--}}
<div class="ls-paper ls-paper--{{ strtolower($paper) }}">
    <table class="ls-kop" role="presentation">
        <tr>
            <td class="ls-kop__logo">
                @if ($letterhead['logo'])
                    <img src="{{ $letterhead['logo'] }}" alt="Logo {{ $letterhead['name'] }}" width="72" height="72" />
                @endif
            </td>
            <td class="ls-kop__text">
                <div class="ls-kop__name">{{ $letterhead['name'] }}</div>
                @foreach ($letterhead['lines'] as $line)
                    <div class="ls-kop__line">{{ $line }}</div>
                @endforeach
            </td>
            <td class="ls-kop__logo"></td>
        </tr>
    </table>
    <div class="ls-kop__rule"></div>

    <div class="ls-body">{!! $body !!}</div>

    @if ($qr)
        <table class="ls-verify" role="presentation">
            <tr>
                <td style="width: 90px;"><img src="{{ $qr }}" alt="QR verifikasi surat" width="80" height="80" /></td>
                <td class="ls-verify__text">
                    Surat ini terdaftar resmi di {{ config('app.name') }}.<br />
                    Pindai QR untuk memeriksa keaslian surat.
                </td>
            </tr>
        </table>
    @endif
</div>
