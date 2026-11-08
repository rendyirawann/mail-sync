{{--
    Setup AJAX global: kirim X-CSRF-TOKEN otomatis (jQuery, axios, fetch same-origin),
    tampilkan error validasi 422 per field, dan tangani 419 (sesi kedaluwarsa)
    dengan pesan + tombol muat ulang. Di-include sesudah plugins.bundle.js.
--}}
<script>
    (function () {
        var meta = document.querySelector('meta[name="csrf-token"]');
        var token = meta ? meta.getAttribute('content') : '';

        function sessionExpired() {
            var text = 'Sesi kedaluwarsa, silakan muat ulang halaman lalu coba lagi.';
            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sesi Kedaluwarsa',
                    text: text,
                    confirmButtonText: 'Muat Ulang',
                    allowOutsideClick: false
                }).then(function () { window.location.reload(); });
            } else if (window.confirm(text)) {
                window.location.reload();
            }
        }

        // Tampilkan pesan validasi 422 di bawah field yang bersangkutan.
        window.showValidationErrors = function (form, errors) {
            if (!form || !errors) { return; }
            form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
            form.querySelectorAll('.invalid-feedback[data-ajax]').forEach(function (el) { el.remove(); });
            Object.keys(errors).forEach(function (field) {
                var name = field.replace(/\.(\d+)/g, '[$1]');
                var input = form.querySelector('[name="' + name + '"], [name="' + name + '[]"]');
                if (!input) { return; }
                input.classList.add('is-invalid');
                var feedback = document.createElement('div');
                feedback.className = 'invalid-feedback d-block';
                feedback.setAttribute('data-ajax', '');
                feedback.textContent = errors[field][0];
                input.insertAdjacentElement('afterend', feedback);
            });
        };

        if (window.jQuery) {
            jQuery.ajaxSetup({ headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' } });
            jQuery(document).ajaxError(function (event, xhr) {
                if (xhr.status === 419) { sessionExpired(); }
            });
        }

        if (window.axios) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        }

        if (window.fetch) {
            var nativeFetch = window.fetch.bind(window);
            window.fetch = function (input, init) {
                init = init || {};
                var url = typeof input === 'string' ? input : (input && input.url) || '';
                var sameOrigin = url.indexOf('http') !== 0 || url.indexOf(window.location.origin) === 0;
                if (sameOrigin) {
                    var headers = new Headers(init.headers || (typeof input !== 'string' && input.headers) || {});
                    if (!headers.has('X-CSRF-TOKEN')) { headers.set('X-CSRF-TOKEN', token); }
                    init.headers = headers;
                }
                return nativeFetch(input, init).then(function (response) {
                    if (response.status === 419 && sameOrigin) { sessionExpired(); }
                    return response;
                });
            };
        }
    })();
</script>
