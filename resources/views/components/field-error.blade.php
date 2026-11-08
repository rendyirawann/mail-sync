@props(['name'])
{{-- Pesan validasi untuk satu field. Pakai bersama class "@error('x') is-invalid @enderror" pada input. --}}
@error($name)
    <div {{ $attributes->merge(['class' => 'invalid-feedback d-block']) }} role="alert">{{ $message }}</div>
@enderror
