# Mail Sync — panduan untuk Claude

**Mail Sync** — aplikasi web untuk menyinkronkan, mengelola, dan memantau email antar akun dalam satu panel admin yang aman.
Modul saat ini: beranda publik + SEO, autentikasi (lokal & sosial), dashboard, akun saya, manajemen user & peran, log aktivitas, pengaturan aplikasi.

## Tech stack
- PHP 8.4 (min 8.2), **Laravel 12** (konfigurasi di `bootstrap/app.php`, tanpa Kernel.php), PostgreSQL
- Panel: Blade + tema Metronic (Bootstrap 5, jQuery, DataTables) di `public/assets` · Publik: Blade + Tailwind 4 via Vite 7
- Reverb + Echo (realtime), Spatie Permission & Activitylog, Socialite, Yajra DataTables, cybercog/laravel-ban
- Pint, Larastan level 5, ESLint, Prettier, PHPUnit 11, Laravel Boost (MCP `laravel-boost`)

## Perintah penting
```bash
composer install && npm install            # install
php artisan serve --port=8881              # app (APP_URL=http://127.0.0.1:8881)
npm run dev                                # Vite
php artisan reverb:start                   # websocket
php artisan queue:work                     # queue
php artisan test                           # test (SQLite in-memory)
vendor/bin/pint --dirty                    # format
vendor/bin/phpstan analyse                 # analisis statis
npm run build && npm run lint              # aset & lint JS
php artisan app:branding --apply           # buat ulang logo/favicon/OG
php artisan boost:update                   # sinkron .ai/ → .claude/skills & CLAUDE.md
```
Windows: `php artisan tinker` meminta konfirmasi interaktif — gunakan skrip PHP yang mem-bootstrap app.

## Lokasi penting
| Apa | Di mana |
| --- | --- |
| Middleware keamanan | `app/Http/Middleware/{SecurityHeaders,BlockSuspiciousRequests,ProtectAgainstSpam,SanitizeInput}.php` |
| Konfigurasi keamanan / SEO / brand | `config/security.php`, `config/seo.php`, `config/branding.php` |
| Rate limiter, password policy, log keamanan | `app/Providers/AppServiceProvider.php` |
| Exception JSON & alias middleware | `bootstrap/app.php` |
| Identitas per request (`$brand`) | `app/Support/Brand.php` (settings DB > config) |
| Layout | `resources/views/frontend/layout.blade.php`, `auth/app.blade.php`, `backend/layout/app.blade.php` |
| Head SEO | `resources/views/partials/seo.blade.php` |
| AJAX CSRF/422/419 | `resources/views/partials/ajax-setup.blade.php` |
| Komponen | `resources/views/components/` (`honeypot`, `field-error`, `avatar`) |
| Halaman error (mandiri) | `resources/views/errors/` |
| Terjemahan | `lang/id/` |
| Helper IDOR | `App\Http\Controllers\Controller::ownAccountOrFail()` |

## Aturan wajib
- **Jangan pernah menampilkan isi `.env`**, APP_KEY, atau secret apa pun di output/log.
- Semua teks UI memakai `config('app.name')`/`$brand[...]`; pesan, validasi, error dalam **Bahasa Indonesia**.
- Controller tipis → FormRequest → Service/Action → Model; Policy/Gate untuk otorisasi; `$fillable` eksplisit; type hint & return type; PHPDoc Bahasa Indonesia; Conventional Commits.
- Data milik user selalu di-scope ke pemilik (cegah IDOR). Error ke klien pakai `$this->debugMessage($e)`.
- Form publik: `@csrf` + `<x-honeypot />` + middleware `spam` + throttle. Upload: mimes + max + nama acak, tanpa svg/html/php.
- **Jangan ubah CSP tanpa membuka halaman dan mengecek console**; jangan pakai nonce/strict-dynamic; tambah domain lewat `SECURITY_CSP_EXTRA_HOSTS` atau konstanta di `SecurityHeaders`.
- Jangan edit `public/assets/plugins` (vendor tema). Jangan edit blok guidelines yang dikelola Boost (di bawah) secara manual — ubah `.ai/guidelines/` lalu `php artisan boost:update`.
- Sebelum selesai: `pint --dirty`, `phpstan analyse`, `php artisan test`, dan skill `security-review` untuk fitur baru.

## Alur kerja
- Jika plugin **superpowers** terpasang: brainstorming → writing-plans → executing-plans untuk pekerjaan besar, test-driven-development, systematic-debugging saat error, verification-before-completion sebelum menyatakan selesai.
- Pakai MCP `laravel-boost` (`search-docs`, `database-schema`, `tinker`, `browser-logs`) untuk API sesuai versi.

## Skill project (`.ai/skills/`, tersinkron ke `.claude/skills/`)
| Skill | Kapan |
| --- | --- |
| `laravel-feature` | Membuat modul/CRUD baru end-to-end |
| `code-standards` | Menulis/review kode (penamaan, arsitektur, PHPDoc) |
| `security-review` | Sebelum commit/merge fitur, audit OWASP |
| `validation-and-errors` | Validasi, pesan error, JSON 422, halaman error |
| `ui-and-branding` | Layout, komponen, logo, warna, CDN & CSP |
| `seo-page` | Halaman publik, meta, sitemap, noindex |
| `testing` | Menulis/menjalankan test |
| `deployment` | Rilis ke production |
| `project-setup` | Setup ulang saat folder disalin jadi project baru |

Plugin eksternal yang disarankan: superpowers, example-skills (anthropics/skills — frontend-design, webapp-testing, skill-creator), semgrep, supply-chain-risk-auditor, ask-questions-if-underspecified.

## Dokumentasi
[README](README.md) · [Arsitektur](docs/ARCHITECTURE.md) · [Database](docs/DATABASE.md) · [Keamanan](docs/SECURITY.md) · [Deployment](docs/DEPLOYMENT.md) · [API](docs/API.md) · [Changelog](docs/CHANGELOG.md)

<laravel-boost-guidelines>
=== .ai/mail-sync rules ===

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

=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- This project uses the streamlined Laravel 11+ structure: register middleware, exceptions, and routing in `bootstrap/app.php` and service providers in `bootstrap/providers.php`. There is no `app/Http/Kernel.php` or `app/Console/Kernel.php`, and commands in `app/Console/Commands/` auto-register.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.

- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
