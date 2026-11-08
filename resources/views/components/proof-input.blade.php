@props(['name' => 'proof'])
{{--
    Input bukti transfer: tempel screenshot (Ctrl+V), seret-lepas, atau pilih file.
    Gambar tempelan dimasukkan ke <input type="file"> lewat DataTransfer, jadi
    form tetap dikirim sebagai upload biasa (tervalidasi server: png/jpg/jpeg/pdf, maks 5 MB).
--}}
<div class="proof-input" data-proof-input>
    <label class="form-label fw-semibold required" for="{{ $name }}-file">Bukti transfer</label>

    <div class="proof-input__zone border border-2 border-dashed rounded-3 p-6 text-center @error($name) border-danger @else border-gray-300 @enderror"
        tabindex="0" role="button" aria-describedby="{{ $name }}-help" data-proof-zone>
        <div data-proof-empty>
            <i class="ki-duotone ki-picture fs-3x text-primary mb-3"><span class="path1"></span><span class="path2"></span></i>
            <div class="fw-bold text-gray-800">Tempel screenshot di sini (Ctrl+V)</div>
            <div class="text-muted fs-7 mt-1">atau seret file ke kotak ini / klik untuk memilih — PNG, JPG, JPEG, PDF (maks. 5 MB)</div>
        </div>
        <div class="d-none" data-proof-preview>
            <img alt="Pratinjau bukti transfer" class="mw-100 rounded mb-3 d-none" style="max-height: 260px;" data-proof-img />
            <div class="fw-semibold text-gray-800" data-proof-name></div>
            <button type="button" class="btn btn-sm btn-light-danger mt-3" data-proof-clear>Ganti bukti</button>
        </div>
    </div>

    <input type="file" class="d-none" id="{{ $name }}-file" name="{{ $name }}" accept=".png,.jpg,.jpeg,.pdf,image/png,image/jpeg,application/pdf" data-proof-file />
    <div id="{{ $name }}-help" class="form-text">Bukti disimpan privat dan hanya bisa dibuka Superadmin.</div>
    <x-field-error :name="$name" />
</div>

@once
    @push('scripts')
        <script>
            (function () {
                var MAX = 5 * 1024 * 1024;
                var ALLOWED = ['image/png', 'image/jpeg', 'application/pdf'];

                document.querySelectorAll('[data-proof-input]').forEach(function (root) {
                    var zone = root.querySelector('[data-proof-zone]');
                    var input = root.querySelector('[data-proof-file]');
                    var empty = root.querySelector('[data-proof-empty]');
                    var preview = root.querySelector('[data-proof-preview]');
                    var img = root.querySelector('[data-proof-img]');
                    var label = root.querySelector('[data-proof-name]');

                    function use(file) {
                        if (!file) { return; }
                        if (ALLOWED.indexOf(file.type) === -1) { window.alert('Format harus PNG, JPG, JPEG, atau PDF.'); return; }
                        if (file.size > MAX) { window.alert('Ukuran maksimal 5 MB.'); return; }

                        if (!file.name || file.name === 'image.png') {
                            file = new File([file], 'bukti-transfer-' + Date.now() + '.png', { type: file.type });
                        }
                        var dt = new DataTransfer();
                        dt.items.add(file);
                        input.files = dt.files;

                        empty.classList.add('d-none');
                        preview.classList.remove('d-none');
                        label.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                        if (file.type.indexOf('image/') === 0) {
                            img.src = URL.createObjectURL(file);
                            img.classList.remove('d-none');
                        } else {
                            img.classList.add('d-none');
                        }
                    }

                    zone.addEventListener('click', function (e) {
                        if (!e.target.closest('[data-proof-clear]')) { input.click(); }
                    });
                    zone.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); }
                    });
                    input.addEventListener('change', function () { use(input.files[0]); });

                    ['dragover', 'dragenter'].forEach(function (ev) {
                        zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('border-primary'); });
                    });
                    ['dragleave', 'drop'].forEach(function (ev) {
                        zone.addEventListener(ev, function () { zone.classList.remove('border-primary'); });
                    });
                    zone.addEventListener('drop', function (e) { e.preventDefault(); use(e.dataTransfer.files[0]); });

                    // Ctrl+V di mana saja pada halaman (kecuali sedang mengetik di kolom teks).
                    document.addEventListener('paste', function (e) {
                        var target = e.target;
                        if (target && (target.tagName === 'TEXTAREA' || (target.tagName === 'INPUT' && target.type !== 'file'))) { return; }
                        var items = (e.clipboardData || {}).items || [];
                        for (var i = 0; i < items.length; i++) {
                            if (items[i].kind === 'file') { e.preventDefault(); use(items[i].getAsFile()); return; }
                        }
                    });

                    root.querySelector('[data-proof-clear]').addEventListener('click', function () {
                        input.value = '';
                        preview.classList.add('d-none');
                        empty.classList.remove('d-none');
                        img.removeAttribute('src');
                    });
                });
            })();
        </script>
    @endpush
@endonce
