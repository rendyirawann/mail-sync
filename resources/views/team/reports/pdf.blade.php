<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>{{ $title }}</title>
    @include('letters._paper-style')
    <style>
        @page { margin: 1.4cm 1.4cm; }
        body { margin: 0; font-family: Helvetica, Arial, sans-serif; font-size: 9pt; }
        h1 { font-size: 13pt; text-align: center; margin: 0 0 2px; }
        .period { text-align: center; margin-bottom: 10px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #333; padding: 4px 5px; vertical-align: top; }
        table.data th { background: #e5e7eb; }
        .meta { margin-top: 8px; color: #555; font-size: 8pt; }
    </style>
</head>

<body>
    <table class="ls-kop" role="presentation">
        <tr>
            <td class="ls-kop__logo">@if ($letterhead['logo'])<img src="{{ $letterhead['logo'] }}" alt="Logo" width="56" height="56" />@endif</td>
            <td class="ls-kop__text">
                <div class="ls-kop__name">{{ $letterhead['name'] }}</div>
                @foreach ($letterhead['lines'] as $line)<div class="ls-kop__line">{{ $line }}</div>@endforeach
            </td>
            <td class="ls-kop__logo"></td>
        </tr>
    </table>
    <div class="ls-kop__rule"></div>

    <h1>{{ strtoupper($title) }}</h1>
    <div class="period">Periode {{ $period }}</div>

    <table class="data">
        <thead><tr><th style="width: 24px;">No</th>@foreach ($headings as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse ($rows as $i => $row)
                <tr><td>{{ $i + 1 }}</td>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($headings) + 1 }}" style="text-align: center;">Tidak ada data pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="meta">Dicetak {{ now()->locale('id')->translatedFormat('j F Y H:i') }} dari {{ config('app.name') }} · {{ count($rows) }} baris</div>
</body>

</html>
