<div class="border rounded p-3 mb-3" data-field>
    <div class="row g-2">
        <div class="col-6">
            <input name="fields[{{ $i }}][label]" class="form-control form-control-sm form-control-solid" placeholder="Label, mis. Tempat acara" value="{{ $f['label'] ?? '' }}" aria-label="Label isian" required />
        </div>
        <div class="col-6">
            <input name="fields[{{ $i }}][key]" class="form-control form-control-sm form-control-solid font-monospace" placeholder="kunci, mis. tempat" value="{{ $f['key'] ?? '' }}" aria-label="Kunci isian" pattern="[a-z][a-z0-9_]*" required />
        </div>
        <div class="col-5">
            <select name="fields[{{ $i }}][type]" class="form-select form-select-sm form-select-solid" aria-label="Jenis isian">
                @foreach (['text' => 'Teks singkat', 'textarea' => 'Paragraf', 'date' => 'Tanggal'] as $v => $l)
                    <option value="{{ $v }}" @selected(($f['type'] ?? 'text') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-5 d-flex align-items-center">
            <label class="form-check form-check-sm form-check-custom form-check-solid">
                <input class="form-check-input" type="checkbox" name="fields[{{ $i }}][required]" value="1" @checked($f['required'] ?? false) />
                <span class="form-check-label fs-7">Wajib</span>
            </label>
        </div>
        <div class="col-2 text-end">
            <button type="button" class="btn btn-sm btn-icon btn-light-danger" data-remove-field aria-label="Hapus isian"><i class="ki-outline ki-trash fs-5"></i></button>
        </div>
        <div class="col-12">
            <input name="fields[{{ $i }}][default]" class="form-control form-control-sm form-control-solid" placeholder="Nilai bawaan (opsional)" value="{{ $f['default'] ?? '' }}" aria-label="Nilai bawaan" />
        </div>
    </div>
</div>
