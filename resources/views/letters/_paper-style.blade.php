{{-- Gaya kertas surat. Dipakai di PDF dan pratinjau — hanya CSS 2.1 agar dompdf mendukung. --}}
<style>
    .ls-paper { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.35; color: #000; background: #fff; }
    .ls-kop { width: 100%; border-collapse: collapse; }
    .ls-kop td { vertical-align: middle; }
    .ls-kop__logo { width: 80px; text-align: center; }
    .ls-kop__text { text-align: center; }
    .ls-kop__name { font-size: 15pt; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; }
    .ls-kop__line { font-size: 10pt; }
    .ls-kop__rule { border-top: 3px solid #000; border-bottom: 1px solid #000; height: 2px; margin: 6px 0 18px; }
    .ls-body p { margin: 0 0 10px; text-align: justify; }
    .ls-body table { border-collapse: collapse; }
    .ls-body td { vertical-align: top; padding: 1px 4px 1px 0; }
    .ls-body .ls-sign { width: 45%; margin-left: 55%; margin-top: 24px; text-align: left; }
    .ls-body .ls-sign__space { height: 70px; }
    .ls-verify { width: 100%; margin-top: 28px; border-top: 1px dashed #999; padding-top: 6px; }
    .ls-verify__text { font-family: Helvetica, Arial, sans-serif; font-size: 8pt; color: #444; vertical-align: middle; }
    .ls-signrow { width: 100%; margin-top: 20px; }
    .ls-signrow td { vertical-align: bottom; }
    .ls-signrow__qr { width: 55%; }
    .ls-signrow__sign { width: 45%; }
    .ls-qr-caption { font-family: Helvetica, Arial, sans-serif; font-size: 7pt; color: #555; margin-top: 2px; }
    [data-pv] { white-space: normal; }
</style>
