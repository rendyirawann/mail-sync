<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Team;
use App\Services\Letters\LetterRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Laporan buku agenda surat masuk / keluar / rekap disposisi per periode,
 * diunduh sebagai PDF atau Excel. Tetap bisa diunduh saat tim beku.
 */
class ReportController extends Controller
{
    private const TYPES = [
        'masuk' => 'Buku Agenda Surat Masuk',
        'keluar' => 'Buku Agenda Surat Keluar',
        'disposisi' => 'Rekap Disposisi',
    ];

    public function index(Team $team): View
    {
        return view('team.reports.index', ['types' => self::TYPES]);
    }

    public function download(Request $request, Team $team, LetterRenderer $renderer): Response
    {
        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(self::TYPES))],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'format' => ['required', 'in:pdf,xlsx'],
        ], [], ['type' => 'jenis laporan', 'from' => 'tanggal awal', 'to' => 'tanggal akhir', 'format' => 'format']);

        $from = Carbon::parse($data['from'])->startOfDay();
        $to = Carbon::parse($data['to'])->endOfDay();
        [$headings, $rows] = $this->rows($data['type'], $from, $to);

        $title = self::TYPES[$data['type']];
        $period = LetterRenderer::dateId($from).' – '.LetterRenderer::dateId($to);
        $filename = str_replace(' ', '-', strtolower($title)).'-'.$from->format('Ymd').'-'.$to->format('Ymd');

        if ($data['format'] === 'xlsx') {
            return $this->excel($title, $period, $headings, $rows, $filename.'.xlsx');
        }

        return Pdf::loadView('team.reports.pdf', [
            'title' => $title,
            'period' => $period,
            'headings' => $headings,
            'rows' => $rows,
            'letterhead' => $renderer->letterhead($team, true),
        ])->setPaper('a4', 'landscape')->setOption(['isRemoteEnabled' => false, 'defaultFont' => 'helvetica'])
            ->download($filename.'.pdf');
    }

    /**
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function rows(string $type, Carbon $from, Carbon $to): array
    {
        $date = fn ($d) => $d ? Carbon::parse($d)->format('d/m/Y') : '-';

        return match ($type) {
            'masuk' => [
                ['No. Agenda', 'Tgl Diterima', 'Nomor Surat', 'Tgl Surat', 'Pengirim', 'Perihal', 'Sifat', 'Status'],
                IncomingLetter::whereBetween('received_date', [$from, $to])->orderBy('received_date')->orderBy('agenda_number')->get()
                    ->map(fn (IncomingLetter $l) => [$l->agendaLabel(), $date($l->received_date), $l->letter_number, $date($l->letter_date),
                        $l->sender, $l->subject, $l->nature->label(), $l->status->label()])->all(),
            ],
            'keluar' => [
                ['Nomor Surat', 'Tanggal', 'Tujuan', 'Perihal', 'Sifat', 'Status', 'Disetujui'],
                OutgoingLetter::with('approver:id,name')->whereBetween('letter_date', [$from, $to])->orderBy('letter_date')->get()
                    ->map(fn (OutgoingLetter $l) => [$l->letter_number ?? '(belum terbit)', $date($l->letter_date), $l->recipient, $l->subject,
                        $l->nature->label(), $l->status->label(), $l->approver->name ?? '-'])->all(),
            ],
            default => [
                ['Tanggal', 'Agenda', 'Perihal Surat', 'Kepada', 'Instruksi', 'Tenggat', 'Status', 'Selesai'],
                Disposition::with(['letter:id,subject,agenda_number,agenda_year', 'recipient:id,name'])
                    ->whereBetween('created_at', [$from, $to])->orderBy('created_at')->get()
                    ->map(fn (Disposition $d) => [$date($d->created_at), $d->letter?->agendaLabel() ?? '-', $d->letter->subject ?? '-',
                        $d->recipient->name ?? '-', $d->instruction, $date($d->due_date), $d->isOverdue() ? 'Lewat tenggat' : $d->status->label(),
                        $date($d->completed_at)])->all(),
            ],
        };
    }

    /**
     * @param  list<string>  $headings
     * @param  list<list<string>>  $rows
     */
    private function excel(string $title, string $period, array $headings, array $rows, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan');
        $sheet->setCellValue('A1', $title);
        $sheet->setCellValue('A2', 'Periode: '.$period);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->fromArray($headings, null, 'A4');
        $sheet->getStyle('A4:'.$sheet->getHighestColumn().'4')->getFont()->setBold(true);

        // Tulis sebagai string eksplisit agar isi seperti "=..." tidak dieksekusi sebagai formula (CSV/formula injection).
        foreach ($rows as $r => $row) {
            foreach ($row as $c => $value) {
                $sheet->setCellValueExplicit([$c + 1, $r + 5], (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        foreach (range(1, count($headings)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
