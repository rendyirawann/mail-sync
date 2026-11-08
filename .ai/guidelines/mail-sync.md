# Mail Sync — Project Guidelines

## Code standards
- Laravel 12 conventions: middleware & exceptions in `bootstrap/app.php` (no Kernel.php). Use `config()` in code; `env()` only inside `config/`.
- Thin controllers → FormRequest (rules + `authorize()` + Indonesian `messages()`/`attributes()`) → Service/Action in `app/Services` or `app/Actions` → Eloquent. Wrap multi-table writes in `DB::transaction()`.
- Authorization via Policy/Gate or `can:` middleware. For "my account" routes use `$this->ownAccountOrFail($id)` from `App\Http\Controllers\Controller`.
- Models: explicit `$fillable`, sensitive attributes in `$hidden`, `casts()` method. Statuses/types as PHP Enums in `app/Enums`. Eager load relations (lazy loading is logged in non-production).
- Typed parameters and return types everywhere, constructor property promotion, `match`, early returns.
- PHPDoc in Bahasa Indonesia on every class and public method; comments explain WHY.
- Naming: singular PascalCase models, plural snake_case tables, `StoreXRequest`/`UpdateXRequest`, kebab-case URLs, dotted route names.
- Run `vendor/bin/pint --dirty` and `vendor/bin/phpstan analyse` (level 5) before finishing. Conventional Commits.

## Security
- Never print `.env` values, APP_KEY, or secrets. Never log passwords/tokens.
- CSP lives in `app/Http/Middleware/SecurityHeaders.php` + `config/security.php`. Add hosts via `SECURITY_CSP_EXTRA_HOSTS` or the constants there; never add nonces/strict-dynamic. Check the browser console for CSP violations after changes.
- Public forms: `@csrf` + `<x-honeypot />` + `spam` middleware + a throttle limiter.
- Uploads: `mimes` + `max`, random filename, never accept svg/html/php.
- Security events go to `Log::channel('security')`.

## UI
- All UI text uses `config('app.name')` / `$brand[...]` — never hardcode the app name.
- User-facing messages, validation, and errors in Bahasa Indonesia.
- Public pages extend `frontend.layout` (Tailwind via Vite); panel pages extend `backend.layout.app` (Metronic in `public/assets`).
