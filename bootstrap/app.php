<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'forbid-banned-user' => \Cog\Laravel\Ban\Http\Middleware\ForbidBannedUser::class,
            'spam' => \App\Http\Middleware\ProtectAgainstSpam::class,
        ]);

        // Runs on every request, including assets served through PHP:
        // header hardening first, then the URL-signature guard.
        $middleware->prepend([
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\BlockSuspiciousRequests::class,
        ]);

        $middleware->web(append: [
            // Invalidates other sessions when the password changes and powers
            // "logout other devices".
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \App\Http\Middleware\SanitizeInput::class,
            \App\Http\Middleware\CheckMaintenanceMode::class,
            'throttle:web',
        ]);

        $middleware->api(prepend: [
            \App\Http\Middleware\SanitizeInput::class,
        ]);

        // CSRF applies to every stateful route. Add a path here only for an
        // endpoint that authenticates by signature instead (e.g. a webhook).
        $middleware->validateCsrfTokens(except: []);

        // Proxy tepercaya diatur di AppServiceProvider::configureProxies() dari
        // config('security.trusted_proxies') — env() di sini bernilai null
        // setelah `php artisan config:cache`.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Request JSON (XHR / api/*) selalu mendapat format {status, message, errors?}
        // dengan pesan Bahasa Indonesia; request web memakai view errors/{kode}.
        $exceptions->shouldRenderJsonWhen(
            fn (\Illuminate\Http\Request $request) => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null; // biarkan Laravel merender view errors.*
            }

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->validator->errors()->first() ?: 'Data yang dikirim tidak valid.',
                    'errors' => $e->errors(),
                ], $e->status);
            }

            $status = match (true) {
                $e instanceof \Illuminate\Auth\AuthenticationException => 401,
                $e instanceof \Illuminate\Auth\Access\AuthorizationException => 403,
                $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => 404,
                $e instanceof \Illuminate\Session\TokenMismatchException => 419,
                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface => $e->getStatusCode(),
                default => 500,
            };

            $defaults = [
                400 => 'Permintaan tidak valid.',
                401 => 'Silakan masuk terlebih dahulu.',
                403 => 'Anda tidak memiliki akses ke halaman ini.',
                404 => 'Data atau halaman tidak ditemukan.',
                405 => 'Metode permintaan tidak diizinkan.',
                408 => 'Waktu permintaan habis.',
                413 => 'Ukuran file terlalu besar.',
                419 => 'Sesi Anda telah kedaluwarsa. Silakan muat ulang halaman dan coba lagi.',
                429 => 'Terlalu banyak permintaan.',
                503 => 'Layanan sedang dalam pemeliharaan.',
            ];

            $message = $defaults[$status] ?? 'Terjadi kesalahan pada server.';
            $headers = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $e->getHeaders() : [];

            // Pesan kustom dari abort(4xx, '...') ditampilkan; pesan bawaan framework & 5xx tidak.
            $custom = trim($e->getMessage());
            if ($status >= 400 && $status < 500 && $custom !== '' && $status !== 429
                && ! preg_match('/^(This action is unauthorized|The route|No query results|Unauthenticated|The \S+ method is not supported|CSRF token mismatch|Too Many Attempts|Not Found|Invalid signature)/i', $custom)) {
                $message = $custom;
            }

            if ($status === 429) {
                $message = 'Terlalu banyak permintaan. Coba lagi dalam '.(int) ($headers['Retry-After'] ?? 60).' detik.';
            }

            $payload = ['status' => 'error', 'message' => $message];

            if ($status >= 500 && config('app.debug')) {
                $payload['debug'] = ['exception' => $e::class, 'detail' => $e->getMessage()];
            }

            return response()->json($payload, $status, $headers);
        });

        // Catat penolakan akses ke channel security (A09).
        $exceptions->report(function (\Illuminate\Auth\Access\AuthorizationException $e) {
            \Illuminate\Support\Facades\Log::channel('security')->warning('Akses ditolak (403)', [
                'user_id' => auth()->id(),
                'path' => request()->path(),
                'ip' => request()->ip(),
            ]);
        });
    })->create();
