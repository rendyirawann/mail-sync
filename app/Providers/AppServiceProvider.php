<?php

namespace App\Providers;

use App\Support\Brand;
use App\View\Composers\NotificationComposer;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Resolved once per request (and reset between Octane requests).
        $this->app->scoped(Brand::KEY, fn () => Brand::compute());
    }

    public function boot(): void
    {
        $this->configureProxies();
        $this->configureUrls();
        $this->configureAuthorization();
        $this->configurePasswordPolicy();
        $this->configureModels();
        $this->logSecurityEvents();
        $this->configureRateLimiting();
        $this->shareBranding();
    }

    /**
     * Percayai header X-Forwarded-* hanya dari proxy yang dikonfigurasi agar
     * $request->ip() adalah IP klien asli (penting untuk rate limiter & log).
     */
    private function configureProxies(): void
    {
        $proxies = trim((string) config('security.trusted_proxies', ''));

        if ($proxies !== '') {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }
    }

    /** Force canonical absolute URLs behind TLS in production. */
    private function configureUrls(): void
    {
        if (config('app.env') === 'production') {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }
    }

    private function configureAuthorization(): void
    {
        // Superadmin implicitly holds every permission.
        Gate::before(function ($user, $ability) {
            return $user->hasRole(['Superadmin', 'superadmin']) ? true : null;
        });
    }

    /**
     * Deteksi N+1 dan mass-assignment yang terbuang di local/staging.
     * Pelanggaran DICATAT (bukan exception) agar halaman lama tidak tiba-tiba 500.
     */
    private function configureModels(): void
    {
        $strict = ! $this->app->isProduction();

        Model::preventLazyLoading($strict);
        Model::preventSilentlyDiscardingAttributes($strict);

        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
            Log::warning('N+1 terdeteksi: lazy loading relasi', ['model' => $model::class, 'relation' => $relation]);
        });

        Model::handleDiscardedAttributeViolationUsing(function (Model $model, array $keys): void {
            Log::warning('Atribut dibuang karena tidak ada di $fillable', ['model' => $model::class, 'keys' => $keys]);
        });
    }

    /**
     * Catat event autentikasi ke channel "security" (OWASP A09).
     * Hanya ID, email ter-mask, dan IP — tanpa password/token.
     */
    private function logSecurityEvents(): void
    {
        $context = static fn (): array => ['ip' => request()->ip(), 'user_agent' => substr((string) request()->userAgent(), 0, 160)];
        $mask = static fn (?string $email): string => $email ? preg_replace('/(?<=.).(?=[^@]*@)/u', '*', $email) : '-';

        Event::listen(Login::class, fn (Login $e) => Log::channel('security')->info('Login berhasil', ['user_id' => $e->user->getAuthIdentifier()] + $context()));
        Event::listen(Failed::class, fn (Failed $e) => Log::channel('security')->warning('Login gagal', ['email' => $mask($e->credentials['email'] ?? null)] + $context()));
        Event::listen(Logout::class, fn (Logout $e) => Log::channel('security')->info('Logout', ['user_id' => $e->user->getAuthIdentifier()] + $context()));
        Event::listen(Lockout::class, fn (Lockout $e) => Log::channel('security')->warning('Login dikunci (throttle)', $context()));
        Event::listen(PasswordReset::class, fn (PasswordReset $e) => Log::channel('security')->info('Password direset', ['user_id' => $e->user->getAuthIdentifier()] + $context()));
    }

    /** One password policy for registration, reset and change-password. */
    private function configurePasswordPolicy(): void
    {
        Password::defaults(function () {
            $rule = Password::min(8)->letters()->mixedCase()->numbers();

            // uncompromised() memanggil API HaveIBeenPwned — hanya di production.
            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });
    }

    /**
     * Named limiters used across web.php, auth.php and api.php.
     *
     * Authenticated users are keyed by id so several people behind one office
     * NAT never throttle each other.
     */
    private function configureRateLimiting(): void
    {
        $limits = config('security.rate_limits');

        RateLimiter::for('web', fn (Request $request) => Limit::perMinute($limits['web'])
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute($limits['api'])
            ->by($request->user()?->id ?: $request->ip()));

        // Writes are cheap to abuse and expensive to serve — keep them tighter.
        RateLimiter::for('write', fn (Request $request) => Limit::perMinute($limits['write'])
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute($limits['login'])
                ->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute($limits['login'] * 4)->by($request->ip()),
        ]);

        RateLimiter::for('password', fn (Request $request) => Limit::perMinute($limits['password'])
            ->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
    }

    /** Expose settings + resolved brand identity to every view. */
    private function shareBranding(): void
    {
        View::composer('*', function ($view) {
            $view->with('brand', Brand::all());

            // Kept for backwards compatibility with existing templates.
            $view->with('appSettings', $this->legacySettings());
        });

        View::composer('backend.layout.navbar', NotificationComposer::class);
    }

    /** @return array<string,string> */
    private function legacySettings(): array
    {
        try {
            return \App\Models\Setting::allCached();
        } catch (\Throwable) {
            return [];
        }
    }
}
