<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>{{ $letter->subject }} — {{ $letter->letter_number ?? 'Draf' }}</title>
    @include('letters._paper-style')
    <style>
        @page { margin: 2cm 2.2cm 2cm 2.5cm; }
        body { margin: 0; }
    </style>
</head>

<body>
    @include('letters._paper')
</body>

</html>
