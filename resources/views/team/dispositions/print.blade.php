<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Lembar Disposisi — {{ $d->letter->agendaLabel() }}</title>
    @include('letters._paper-style')
    <style>
        body { margin: 0; background: #f1f5f9; }
        .sheet { width: 210mm; min-height: 148mm; margin: 16px auto; background: #fff; padding: 14mm 16mm; box-sizing: border-box; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
        .title { text-align: center; font-weight: bold; font-size: 14pt; letter-spacing: 1px; margin: 4px 0 12px; }
        .grid { width: 100%; border-collapse: collapse; font-size: 11pt; }
        .grid td { border: 1px solid #000; padding: 6px 8px; vertical-align: top; }
        .grid .label { width: 26%; background: #f8fafc; }
        .actions { text-align: center; margin: 12px 0; font-family: Arial, sans-serif; }
        .actions button { padding: 8px 18px; font-size: 14px; cursor: pointer; }
        @media print { body { background: #fff; } .sheet { margin: 0; box-shadow: none; } .actions { display: none; } }
    </style>
</head>

<body>
    <div class="actions"><button type="button" onclick="window.print()">Cetak lembar disposisi</button></div>
    <div class="sheet ls-paper">
        <table class="ls-kop" role="presentation">
            <tr>
                <td class="ls-kop__logo">@if ($letterhead['logo'])<img src="{{ $letterhead['logo'] }}" alt="Logo" width="64" height="64" />@endif</td>
                <td class="ls-kop__text">
                    <div class="ls-kop__name">{{ $letterhead['name'] }}</div>
                    @foreach ($letterhead['lines'] as $line)<div class="ls-kop__line">{{ $line }}</div>@endforeach
                </td>
                <td class="ls-kop__logo"></td>
            </tr>
        </table>
        <div class="ls-kop__rule"></div>
        <div class="title">LEMBAR DISPOSISI</div>

        <table class="grid">
            <tr><td class="label">Nomor agenda</td><td>{{ $d->letter->agendaLabel() }}</td><td class="label">Sifat</td><td>{{ $d->letter->nature->label() }}</td></tr>
            <tr><td class="label">Asal surat</td><td colspan="3">{{ $d->letter->sender }}</td></tr>
            <tr><td class="label">Nomor / tanggal</td><td colspan="3">{{ $d->letter->letter_number }} / {{ \App\Services\Letters\LetterRenderer::dateId($d->letter->letter_date) }}</td></tr>
            <tr><td class="label">Diterima</td><td colspan="3">{{ \App\Services\Letters\LetterRenderer::dateId($d->letter->received_date) }}</td></tr>
            <tr><td class="label">Perihal</td><td colspan="3"><strong>{{ $d->letter->subject }}</strong></td></tr>
            <tr><td class="label">Diteruskan kepada</td><td colspan="3">{{ $d->recipient?->name }}{{ $d->recipient?->position ? ' — '.$d->recipient->position : '' }}{{ $d->recipient?->division ? ' ('.$d->recipient->division->name.')' : '' }}</td></tr>
            <tr><td class="label">Instruksi</td><td colspan="3" style="height: 70px;">{!! nl2br(e($d->instruction)) !!}</td></tr>
            <tr><td class="label">Tenggat</td><td>{{ $d->due_date ? \App\Services\Letters\LetterRenderer::dateId($d->due_date) : '-' }}</td><td class="label">Pemberi disposisi</td><td>{{ $d->sender?->name ?? '-' }}<br>{{ \App\Services\Letters\LetterRenderer::dateId($d->created_at) }}</td></tr>
            <tr><td class="label">Tindak lanjut</td><td colspan="3" style="height: 60px;">{{ $d->response }}</td></tr>
        </table>
    </div>
</body>

</html>
