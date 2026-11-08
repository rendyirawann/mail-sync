<?php

namespace App\Services\Letters;

use App\Models\Employee;
use App\Models\LetterTemplate;
use App\Models\OutgoingLetter;
use App\Models\Team;
use App\Support\SafeRemoteImage;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

/**
 * Satu sumber untuk pratinjau (browser) dan PDF (dompdf): keduanya memakai
 * view letters.print. Penanda {{kunci}} di template diganti nilai yang
 * sudah di-escape dan dibungkus <span data-pv="kunci"> supaya JS di halaman
 * isi bisa memperbarui pratinjau secara langsung saat user mengetik.
 */
class LetterRenderer
{
    /** Penanda sistem yang selalu tersedia di setiap template. */
    public const SYSTEM_FIELDS = [
        'nomor_surat' => 'Nomor surat',
        'tanggal_surat' => 'Tanggal surat',
        'tujuan' => 'Tujuan / penerima',
        'perihal' => 'Perihal',
        'sifat' => 'Sifat',
        'penandatangan_nama' => 'Nama penanda tangan',
        'penandatangan_jabatan' => 'Jabatan penanda tangan',
        'penandatangan_nip' => 'NIP penanda tangan',
        'nama_instansi' => 'Nama instansi',
    ];

    /** Placeholder yang tampil bila isian masih kosong (seperti formulir kertas). */
    public const EMPTY_MARK = '..........';

    /**
     * Gabungkan nilai isian user + nilai sistem dari data surat.
     *
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    public function values(Team $team, OutgoingLetter $letter, array $content, ?Employee $signer): array
    {
        $date = $letter->letter_date ?? now();

        return array_merge(
            array_map(fn ($v) => is_scalar($v) ? (string) $v : '', $content),
            [
                'nomor_surat' => $letter->letter_number ?: '(nomor terbit saat disetujui)',
                'tanggal_surat' => self::dateId($date),
                'tujuan' => (string) $letter->recipient,
                'perihal' => (string) $letter->subject,
                'sifat' => $letter->nature?->label() ?? 'Biasa',
                'penandatangan_nama' => (string) $signer?->name,
                'penandatangan_jabatan' => (string) $signer?->position,
                'penandatangan_nip' => (string) $signer?->nip,
                'nama_instansi' => (string) $team->profileValue('letterhead.name', $team->name),
            ],
        );
    }

    /**
     * Ganti {{kunci}} dengan nilai ter-escape. Isian multi-baris → <br>.
     *
     * @param  array<string, string>  $values
     */
    public function renderBody(string $body, array $values, ?string $qr = null): string
    {
        return (string) preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', function (array $m) use ($values, $qr): string {
            $key = strtolower($m[1]);

            // {{qr}}: gambar QR verifikasi (hanya setelah surat disahkan).
            if ($key === 'qr') {
                return $qr === null ? '' : '<img src="'.e($qr).'" alt="QR verifikasi" width="78" height="78" />'
                    .'<div class="ls-qr-caption">Pindai untuk cek keaslian</div>';
            }

            $value = trim($values[$key] ?? '');
            $html = $value === '' ? self::EMPTY_MARK : nl2br(e($value), false);

            return '<span data-pv="'.e($key).'">'.$html.'</span>';
        }, $body);
    }

    /**
     * Data lengkap untuk view letters.print.
     *
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public function viewData(Team $team, OutgoingLetter $letter, ?LetterTemplate $template, array $content, ?Employee $signer, bool $forPdf = false): array
    {
        $values = $this->values($team, $letter, $this->withDefaults($template, $content), $signer);
        $qr = $letter->verification_token ? $this->qrDataUri(route('letters.verify', $letter->verification_token)) : null;
        $body = $template !== null ? $this->renderBody($template->body, $values, $qr) : nl2br(e((string) ($content['isi'] ?? '')), false);

        return [
            'team' => $team,
            'letter' => $letter,
            'body' => $body,
            'paper' => $template?->paper ?? 'A4',
            'letterhead' => $this->letterhead($team, $forPdf),
            // Template tanpa {{qr}} tetap mendapat QR di bagian bawah surat.
            'qr' => ($template !== null && str_contains($template->body, '{{qr}}')) ? null : $qr,
            'forPdf' => $forPdf,
        ];
    }

    /**
     * Isian kosong memakai nilai bawaan dari definisi field template.
     *
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public function withDefaults(?LetterTemplate $template, array $content): array
    {
        foreach ($template?->fields ?? [] as $field) {
            $key = $field['key'];

            if (trim((string) ($content[$key] ?? '')) === '' && isset($field['default'])) {
                $content[$key] = $field['default'];
            }
        }

        return $content;
    }

    /**
     * Data kop surat. Untuk PDF, logo di-embed sebagai data URI (dompdf tidak
     * pernah mengambil URL sendiri); untuk pratinjau, browser memuat URL-nya.
     *
     * @return array{name: string, lines: list<string>, logo: ?string}
     */
    public function letterhead(Team $team, bool $forPdf = false): array
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", (string) $team->profileValue('letterhead.lines', '')))));
        $logoUrl = SafeRemoteImage::normalizeUrl($team->profileValue('letterhead.logo_url'));

        return [
            'name' => (string) $team->profileValue('letterhead.name', $team->name),
            'lines' => $lines ?: array_values(array_filter([$team->address, $team->phone ? 'Telp. '.$team->phone : null, $team->email])),
            'logo' => $forPdf ? SafeRemoteImage::dataUri($logoUrl) : $this->safeImageUrl($logoUrl),
        ];
    }

    /**
     * Buat PDF dari surat keluar.
     *
     * @param  array<string, mixed>  $content
     */
    public function pdf(Team $team, OutgoingLetter $letter, ?LetterTemplate $template, array $content, ?Employee $signer): \Barryvdh\DomPDF\PDF
    {
        $data = $this->viewData($team, $letter, $template, $content, $signer, true);

        // F4 = 215 x 330 mm (dalam point).
        $paper = $data['paper'] === 'F4' ? [0, 0, 609.45, 935.43] : 'a4';

        return Pdf::loadView('letters.print', $data)
            ->setPaper($paper)
            ->setOption(['isRemoteEnabled' => false, 'defaultFont' => 'times', 'dpi' => 96]);
    }

    /** QR SVG sebagai data URI (tanpa Imagick, aman untuk dompdf & browser). */
    public function qrDataUri(string $url, int $size = 110): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd));

        return 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($url));
    }

    public static function dateId(\DateTimeInterface $date): string
    {
        return Carbon::instance($date)->locale('id')->translatedFormat('j F Y');
    }

    /** Logo kop hanya dari URL http(s) — tidak boleh skema lain (javascript:, file:). */
    private function safeImageUrl(mixed $url): ?string
    {
        return is_string($url) && preg_match('#^https?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }
}
