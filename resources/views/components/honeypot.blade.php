{{--
    Anti-spam untuk form publik. Pasangan middleware App\Http\Middleware\ProtectAgainstSpam (alias "spam").
    Pakai: letakkan <x-honeypot /> di dalam <form> tepat setelah @csrf.
--}}
<div aria-hidden="true" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;">
    <label for="hp_{{ \App\Http\Middleware\ProtectAgainstSpam::HONEYPOT_FIELD }}">Jangan diisi</label>
    <input type="text" id="hp_{{ \App\Http\Middleware\ProtectAgainstSpam::HONEYPOT_FIELD }}"
        name="{{ \App\Http\Middleware\ProtectAgainstSpam::HONEYPOT_FIELD }}" value="" tabindex="-1" autocomplete="off" />
</div>
<input type="hidden" name="{{ \App\Http\Middleware\ProtectAgainstSpam::TIME_FIELD }}"
    value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) time()) }}" />
@if (config('services.turnstile.enabled'))
    <div class="cf-turnstile my-3" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
    @once
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endonce
@endif
