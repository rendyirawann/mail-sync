@extends('frontend.layout')

@section('title', 'Coba Akun Demo')
@section('meta_description', 'Coba '.config('app.name').' tanpa mendaftar: buat akun demo sekali pakai berisi data contoh. Akun dan datanya terhapus otomatis setelah 24 jam.')
@section('robots', 'noindex, nofollow')

@section('content')
    <section class="bg-slate-50">
        <div class="mx-auto max-w-3xl px-4 py-14 sm:px-6">
            <div class="text-center">
                <span class="inline-flex items-center gap-2 rounded-full border border-brand-600/20 bg-white px-3 py-1 text-xs font-semibold text-brand-700">Tanpa pendaftaran</span>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Masuk sebagai Demo</h1>
                <p class="mx-auto mt-3 max-w-2xl text-slate-600">
                    Buat satu tim demo berisi akun Owner, Admin, Pimpinan, dan Staf beserta contoh surat masuk, disposisi, dan surat keluar.
                    Cocok untuk mencoba alur kerja {{ config('app.name') }} dari berbagai peran.
                </p>
            </div>

            <ul class="mt-8 grid gap-3 text-sm sm:grid-cols-3">
                <li class="rounded-xl border border-slate-200 bg-white p-4">
                    <span class="block font-bold text-slate-900">Berlaku {{ $lifetimeHours }} jam</span>
                    <span class="text-slate-500">Akun & seluruh datanya terhapus otomatis.</span>
                </li>
                <li class="rounded-xl border border-slate-200 bg-white p-4">
                    <span class="block font-bold text-slate-900">Simpan kredensial</span>
                    <span class="text-slate-500">Hanya ditampilkan 3 menit. Salin atau unduh .txt.</span>
                </li>
                <li class="rounded-xl border border-slate-200 bg-white p-4">
                    <span class="block font-bold text-slate-900">1 kali per minggu</span>
                    <span class="text-slate-500">Satu jaringan bisa membuat demo seminggu sekali.</span>
                </li>
            </ul>

            @if ($errors->has('demo'))
                <div class="mt-8 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900" role="alert">
                    {{ $errors->first('demo') }}
                </div>
            @elseif ($errors->any())
                <div class="mt-8 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
                @if ($credentials)
                    <p class="text-slate-700">Akun demo Anda sudah dibuat.</p>
                    <button type="button" data-demo-open class="mt-4 rounded-xl bg-brand-600 px-6 py-3.5 text-sm font-semibold text-white hover:bg-brand-700">Lihat akun demo</button>
                @elseif ($nextAllowedAt)
                    <p class="text-slate-700">Jaringan Anda sudah membuat akun demo minggu ini.</p>
                    <p class="mt-1 text-sm text-slate-500">Bisa membuat lagi pada {{ $nextAllowedAt->timezone(config('app.timezone'))->translatedFormat('d F Y, H:i') }}.</p>
                    <a href="{{ route('register.team') }}" class="mt-4 inline-flex rounded-xl border border-slate-300 bg-white px-6 py-3.5 text-sm font-semibold text-zinc-950 hover:bg-slate-50">Daftarkan Tim</a>
                @else
                    <form method="POST" action="{{ route('demo.store') }}" id="demoForm">
                        @csrf
                        <x-honeypot />
                        <button type="submit" id="demoSubmit" class="rounded-xl bg-brand-600 px-6 py-3.5 text-sm font-semibold text-white shadow-xs hover:bg-brand-700 disabled:opacity-60">
                            Generate akun demo
                        </button>
                    </form>
                    <p class="mt-3 text-xs text-slate-500">Dengan menekan tombol, Anda setuju data demo bersifat sementara dan dapat dilihat siapa pun yang memegang kredensialnya.</p>
                @endif
            </div>
        </div>
    </section>

    @if ($credentials)
        @php
            $lines = [
                config('app.name').' — Akun Demo',
                'Tim       : '.$credentials['team'],
                'Kode tim  : '.$credentials['code'],
                'Login     : '.$credentials['login_url'],
                'Berlaku s/d: '.\Illuminate\Support\Carbon::parse($credentials['expires_at'])->timezone(config('app.timezone'))->translatedFormat('d F Y, H:i').' (dihapus otomatis)',
                '',
            ];
            foreach ($credentials['accounts'] as $acc) {
                $lines[] = str_pad($acc['role'], 9).': '.$acc['email'].' / '.$acc['password'];
            }
            $txt = implode("\n", $lines)."\n";
        @endphp
        <div id="demoModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="demoModalTitle">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="demoModalTitle" class="text-xl font-extrabold text-slate-900">Akun demo berhasil dibuat</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            Simpan sekarang. Daftar ini hilang dalam <strong id="demoCountdown" data-seconds="{{ $secondsLeft }}">{{ intdiv($secondsLeft, 60) }}:{{ str_pad((string) ($secondsLeft % 60), 2, '0', STR_PAD_LEFT) }}</strong>.
                        </p>
                    </div>
                    <button type="button" data-demo-close class="rounded-lg px-2 py-1 text-slate-500 hover:bg-slate-100" aria-label="Tutup">&times;</button>
                </div>

                <dl class="mt-4 grid gap-2 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Kode tim</dt><dd class="font-bold text-slate-900">{{ $credentials['code'] }}</dd></div>
                    <div><dt class="text-slate-500">Halaman login</dt><dd><a href="{{ $credentials['login_url'] }}" class="font-semibold text-brand-700 hover:underline">{{ $credentials['login_url'] }}</a></dd></div>
                </dl>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs uppercase text-slate-500">
                            <tr><th class="py-2 pr-4">Peran</th><th class="py-2 pr-4">Email</th><th class="py-2">Password</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($credentials['accounts'] as $acc)
                                <tr>
                                    <td class="py-2 pr-4 font-semibold text-slate-900">{{ $acc['role'] }}</td>
                                    <td class="py-2 pr-4 font-mono text-slate-700">{{ $acc['email'] }}</td>
                                    <td class="py-2 font-mono text-slate-700">{{ $acc['password'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="mt-4 text-xs text-slate-500">Akun dan seluruh data tim ini terhapus otomatis setelah {{ $lifetimeHours }} jam.</p>

                <div class="mt-5 flex flex-wrap gap-3">
                    <button type="button" id="demoCopy" class="rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white hover:bg-brand-700">Salin</button>
                    <button type="button" id="demoDownload" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-zinc-950 hover:bg-slate-50">Unduh .txt</button>
                    <a href="{{ $credentials['login_url'] }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-zinc-950 hover:bg-slate-50">Ke halaman login</a>
                </div>
                <textarea id="demoText" class="sr-only" readonly aria-hidden="true">{{ $txt }}</textarea>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('demoForm');
    if (form) {
        // Anti-spam menolak kiriman < 3 detik setelah halaman dimuat; klik cepat ditahan lalu dikirim otomatis.
        var loadedAt = Date.now(), MIN = {{ \App\Http\Middleware\ProtectAgainstSpam::MIN_SECONDS }} * 1000 + 600, sent = false;
        form.addEventListener('submit', function (e) {
            var b = document.getElementById('demoSubmit');
            if (sent) { e.preventDefault(); return; }
            var wait = MIN - (Date.now() - loadedAt);
            b.disabled = true; b.textContent = 'Membuat akun...';
            if (wait > 0) {
                e.preventDefault();
                setTimeout(function () { sent = true; form.submit(); }, wait);
            } else {
                sent = true;
            }
        });
    }

    var modal = document.getElementById('demoModal');
    if (!modal) { return; }
    var text = document.getElementById('demoText').value;
    var code = @json($credentials['code'] ?? 'demo');

    document.querySelectorAll('[data-demo-close]').forEach(function (el) {
        el.addEventListener('click', function () { modal.classList.add('hidden'); });
    });
    document.querySelectorAll('[data-demo-open]').forEach(function (el) {
        el.addEventListener('click', function () { modal.classList.remove('hidden'); });
    });

    var copyBtn = document.getElementById('demoCopy');
    copyBtn.addEventListener('click', function () {
        var done = function () { copyBtn.textContent = 'Tersalin ✓'; setTimeout(function () { copyBtn.textContent = 'Salin'; }, 2000); };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done);
        } else {
            var t = document.getElementById('demoText');
            t.classList.remove('sr-only'); t.select(); document.execCommand('copy'); t.classList.add('sr-only'); done();
        }
    });

    document.getElementById('demoDownload').addEventListener('click', function () {
        var url = URL.createObjectURL(new Blob([text], { type: 'text/plain;charset=utf-8' }));
        var a = document.createElement('a');
        a.href = url; a.download = 'akun-demo-' + code + '.txt';
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    });

    var cd = document.getElementById('demoCountdown');
    var left = parseInt(cd.dataset.seconds, 10) || 0;
    var timer = setInterval(function () {
        left -= 1;
        if (left <= 0) {
            clearInterval(timer);
            cd.textContent = '0:00';
            cd.parentElement.textContent = 'Waktu tampil habis. Kredensial tidak bisa ditampilkan lagi.';
            return;
        }
        cd.textContent = Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0');
    }, 1000);
})();
</script>
@endpush
