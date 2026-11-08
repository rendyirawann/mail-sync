<?php

namespace Database\Seeders;

use App\Models\LetterTemplate;
use Illuminate\Database\Seeder;

/**
 * Template surat keluar bawaan (team_id null) — tersedia untuk semua tim.
 * Idempoten: aman dijalankan ulang, template dicocokkan berdasarkan nama.
 *
 * Penanda sistem selalu tersedia: {{nomor_surat}} {{tanggal_surat}} {{tujuan}} {{perihal}}
 * {{sifat}} {{penandatangan_nama}} {{penandatangan_jabatan}} {{penandatangan_nip}} {{nama_instansi}}
 */
class DefaultLetterTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $template) {
            LetterTemplate::withoutGlobalScopes()->updateOrCreate(
                ['team_id' => null, 'name' => $template['name']],
                $template + ['is_active' => true],
            );
        }
    }

    /**
     * @return list<array{name: string, description: string, paper: string, body: string, fields: list<array<string, mixed>>}>
     */
    private function templates(): array
    {
        $header = <<<'HTML'
<table style="width:100%">
<tr><td style="width:90px">Nomor</td><td style="width:10px">:</td><td>{{nomor_surat}}</td><td style="text-align:right">{{kota}}, {{tanggal_surat}}</td></tr>
<tr><td>Sifat</td><td>:</td><td colspan="2">{{sifat}}</td></tr>
<tr><td>Lampiran</td><td>:</td><td colspan="2">{{lampiran}}</td></tr>
<tr><td>Perihal</td><td>:</td><td colspan="2"><strong>{{perihal}}</strong></td></tr>
</table>
<p style="margin-top:16px">Kepada Yth.<br><strong>{{tujuan}}</strong><br>di<br>&nbsp;&nbsp;&nbsp;&nbsp;Tempat</p>
HTML;

        $sign = <<<'HTML'
<table class="ls-signrow"><tr>
<td class="ls-signrow__qr">{{qr}}</td>
<td class="ls-signrow__sign">{{penandatangan_jabatan}},<div class="ls-sign__space"></div>
<strong><u>{{penandatangan_nama}}</u></strong><br>NIP. {{penandatangan_nip}}</td>
</tr></table>
HTML;

        $kota = ['key' => 'kota', 'label' => 'Kota penerbitan', 'type' => 'text', 'required' => true];
        $lampiran = ['key' => 'lampiran', 'label' => 'Lampiran', 'type' => 'text', 'required' => false, 'default' => '-'];

        return [
            [
                'name' => 'Surat Undangan',
                'description' => 'Undangan rapat/kegiatan dengan hari, waktu, dan tempat.',
                'paper' => 'A4',
                'body' => $header.<<<'HTML'
<p>Dengan hormat,</p>
<p>{{pembuka}}</p>
<table style="margin-left:40px">
<tr><td style="width:110px">Hari/Tanggal</td><td style="width:10px">:</td><td>{{hari_tanggal}}</td></tr>
<tr><td>Waktu</td><td>:</td><td>{{waktu}}</td></tr>
<tr><td>Tempat</td><td>:</td><td>{{tempat}}</td></tr>
<tr><td>Acara</td><td>:</td><td>{{acara}}</td></tr>
</table>
<p style="margin-top:10px">Mengingat pentingnya acara tersebut, kami mengharapkan kehadiran Bapak/Ibu tepat waktu. Atas perhatian dan kehadirannya diucapkan terima kasih.</p>
HTML.$sign,
                'fields' => [
                    $kota, $lampiran,
                    ['key' => 'pembuka', 'label' => 'Kalimat pembuka', 'type' => 'textarea', 'required' => true,
                        'default' => 'Sehubungan dengan akan dilaksanakannya kegiatan, kami mengundang Bapak/Ibu untuk hadir pada:'],
                    ['key' => 'hari_tanggal', 'label' => 'Hari / tanggal', 'type' => 'text', 'required' => true],
                    ['key' => 'waktu', 'label' => 'Waktu', 'type' => 'text', 'required' => true, 'default' => '09.00 WIB s.d. selesai'],
                    ['key' => 'tempat', 'label' => 'Tempat', 'type' => 'text', 'required' => true],
                    ['key' => 'acara', 'label' => 'Acara', 'type' => 'text', 'required' => true],
                ],
            ],
            [
                'name' => 'Surat Pemberitahuan',
                'description' => 'Pemberitahuan resmi kepada pihak lain.',
                'paper' => 'A4',
                'body' => $header.<<<'HTML'
<p>Dengan hormat,</p>
<p>{{isi}}</p>
<p>Demikian pemberitahuan ini kami sampaikan. Atas perhatian dan kerja samanya diucapkan terima kasih.</p>
HTML.$sign,
                'fields' => [
                    $kota, $lampiran,
                    ['key' => 'isi', 'label' => 'Isi pemberitahuan', 'type' => 'textarea', 'required' => true],
                ],
            ],
            [
                'name' => 'Surat Tugas',
                'description' => 'Penugasan pegawai untuk kegiatan / perjalanan dinas.',
                'paper' => 'A4',
                'body' => <<<'HTML'
<p style="text-align:center;margin-bottom:0"><strong><u>SURAT TUGAS</u></strong></p>
<p style="text-align:center">Nomor: {{nomor_surat}}</p>
<table>
<tr><td style="width:90px">Dasar</td><td style="width:10px">:</td><td>{{dasar}}</td></tr>
</table>
<p style="text-align:center;margin-top:10px"><strong>MENUGASKAN</strong></p>
<table>
<tr><td style="width:90px">Kepada</td><td style="width:10px">:</td><td>{{nama_pegawai}}<br>NIP. {{nip_pegawai}}<br>Jabatan: {{jabatan_pegawai}}</td></tr>
<tr><td>Untuk</td><td>:</td><td>{{tugas}}</td></tr>
<tr><td>Tanggal</td><td>:</td><td>{{tanggal_tugas}}</td></tr>
<tr><td>Tempat</td><td>:</td><td>{{tempat}}</td></tr>
</table>
<p style="margin-top:10px">Demikian surat tugas ini dibuat untuk dilaksanakan dengan penuh tanggung jawab.</p>
<p style="text-align:right">{{kota}}, {{tanggal_surat}}</p>
HTML.$sign,
                'fields' => [
                    $kota,
                    ['key' => 'dasar', 'label' => 'Dasar penugasan', 'type' => 'textarea', 'required' => true],
                    ['key' => 'nama_pegawai', 'label' => 'Nama pegawai', 'type' => 'text', 'required' => true],
                    ['key' => 'nip_pegawai', 'label' => 'NIP pegawai', 'type' => 'text', 'required' => false, 'default' => '-'],
                    ['key' => 'jabatan_pegawai', 'label' => 'Jabatan pegawai', 'type' => 'text', 'required' => true],
                    ['key' => 'tugas', 'label' => 'Uraian tugas', 'type' => 'textarea', 'required' => true],
                    ['key' => 'tanggal_tugas', 'label' => 'Tanggal pelaksanaan', 'type' => 'text', 'required' => true],
                    ['key' => 'tempat', 'label' => 'Tempat', 'type' => 'text', 'required' => true],
                ],
            ],
            [
                'name' => 'Surat Keterangan',
                'description' => 'Keterangan resmi tentang seseorang / suatu hal.',
                'paper' => 'A4',
                'body' => <<<'HTML'
<p style="text-align:center;margin-bottom:0"><strong><u>SURAT KETERANGAN</u></strong></p>
<p style="text-align:center">Nomor: {{nomor_surat}}</p>
<p>Yang bertanda tangan di bawah ini, {{penandatangan_jabatan}} {{nama_instansi}}, menerangkan bahwa:</p>
<table style="margin-left:40px">
<tr><td style="width:110px">Nama</td><td style="width:10px">:</td><td>{{nama}}</td></tr>
<tr><td>NIK/NIP</td><td>:</td><td>{{nomor_identitas}}</td></tr>
<tr><td>Alamat</td><td>:</td><td>{{alamat}}</td></tr>
</table>
<p style="margin-top:10px">{{keterangan}}</p>
<p>Demikian surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.</p>
<p style="text-align:right">{{kota}}, {{tanggal_surat}}</p>
HTML.$sign,
                'fields' => [
                    $kota,
                    ['key' => 'nama', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['key' => 'nomor_identitas', 'label' => 'NIK / NIP', 'type' => 'text', 'required' => false, 'default' => '-'],
                    ['key' => 'alamat', 'label' => 'Alamat', 'type' => 'textarea', 'required' => false, 'default' => '-'],
                    ['key' => 'keterangan', 'label' => 'Isi keterangan', 'type' => 'textarea', 'required' => true],
                ],
            ],
            [
                'name' => 'Nota Dinas',
                'description' => 'Komunikasi internal antar bidang / pimpinan.',
                'paper' => 'A4',
                'body' => <<<'HTML'
<p style="text-align:center"><strong><u>NOTA DINAS</u></strong></p>
<table>
<tr><td style="width:90px">Kepada</td><td style="width:10px">:</td><td>{{tujuan}}</td></tr>
<tr><td>Dari</td><td>:</td><td>{{penandatangan_jabatan}}</td></tr>
<tr><td>Tanggal</td><td>:</td><td>{{tanggal_surat}}</td></tr>
<tr><td>Nomor</td><td>:</td><td>{{nomor_surat}}</td></tr>
<tr><td>Sifat</td><td>:</td><td>{{sifat}}</td></tr>
<tr><td>Hal</td><td>:</td><td><strong>{{perihal}}</strong></td></tr>
</table>
<div style="border-top:1px solid #000;margin:8px 0 12px"></div>
<p>{{isi}}</p>
<p>Demikian disampaikan, atas perhatiannya diucapkan terima kasih.</p>
HTML.$sign,
                'fields' => [
                    ['key' => 'isi', 'label' => 'Isi nota dinas', 'type' => 'textarea', 'required' => true],
                ],
            ],
        ];
    }
}
