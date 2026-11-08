<?php

use App\Http\Controllers\Backend\Dashboard\DashboardAdminController;
// Frontend
use App\Http\Controllers\Backend\Help\LogActivityController;
use App\Http\Controllers\Backend\MyProfile\AccountController;
// Dashboard
use App\Http\Controllers\Backend\MyProfile\ActivityController;
// My profile
use App\Http\Controllers\Backend\MyProfile\LoginSessionController;
use App\Http\Controllers\Backend\MyProfile\ProfileController;
use App\Http\Controllers\Backend\MyProfile\SecurityController;
use App\Http\Controllers\Backend\Saas\BillingSettingController;
use App\Http\Controllers\Backend\Saas\RegistrationAdminController;
// User management
use App\Http\Controllers\Backend\Saas\TeamAdminController;
use App\Http\Controllers\Backend\Settings\SettingController;
use App\Http\Controllers\Backend\UserManagement\RoleController;
use App\Http\Controllers\Backend\UserManagement\UserController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\LetterVerificationController;
use App\Http\Controllers\Frontend\RegistrationController;
// Help / system
use App\Http\Controllers\Frontend\SeoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- Halaman publik (bisa diindeks) ---
Route::get('/', HomeController::class)->name('home');
Route::get('/masuk', [HomeController::class, 'teamLogin'])->middleware('throttle:login')->name('team.find');

// robots.txt & sitemap.xml dinamis — dikecualikan dari rate limit web.
Route::withoutMiddleware('throttle:web')->group(function () {
    Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
    Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
});

// --- Pendaftaran tim (publik) ---
Route::get('/daftar', [RegistrationController::class, 'create'])->name('register.team');
Route::post('/daftar', [RegistrationController::class, 'store'])->middleware(['throttle:password', 'spam'])->name('register.team.store');
Route::get('/daftar/{registration:code}', [RegistrationController::class, 'show'])->name('register.team.show');

// --- Verifikasi keaslian surat (hasil pindai QR) ---
Route::get('/verifikasi/{token}', LetterVerificationController::class)
    ->where('token', '[A-Za-z0-9]{20,40}')
    ->name('letters.verify');

Route::middleware(['auth', 'forbid-banned-user', 'platform'])->group(function () {

    // --- Dashboard (every authenticated role) ---
    Route::get('/admin/dashboard', [DashboardAdminController::class, 'index'])->name('dashboard');

    // --- My account (every authenticated user) ---
    Route::get('/admin/my-account', [AccountController::class, 'index'])->name('account.index');
    Route::get('/admin/my-account/{id}/avatar', [AccountController::class, 'editAvatar'])->name('avatar-edit');

    Route::get('/admin/my-activity', [ActivityController::class, 'index'])->name('my-activity.index');
    Route::get('/admin/mget-my-activity', [ActivityController::class, 'getActivity'])->name('get-my-activity');

    Route::get('/admin/mmy-login-session', [LoginSessionController::class, 'index'])->name('my-login-session.index');
    Route::get('/admin/mget-my-login-session', [LoginSessionController::class, 'getLoginSession'])->name('get-my-login-session');

    // --- Activity log ---
    // Accessible to every authenticated user; the controller scopes the query
    // so a non-Superadmin only ever sees the entries they caused.
    Route::get('/admin/log-activity', [LogActivityController::class, 'index'])->name('log-activity.index');
    Route::get('/admin/get-datalogactivity', [LogActivityController::class, 'getDataLogActivity'])->name('get-datalogactivity');
    Route::get('/admin/log-activity/{id}/detail', [LogActivityController::class, 'detail'])->name('log-activity.detail');
    Route::get('/admin/log-activity/{id}', [LogActivityController::class, 'show'])->name('log-activity.show');

    // Shared helpers used by the role/user forms.
    Route::get('/admin/select/role', [RoleController::class, 'select'])->name('role.select');

    /*
    |----------------------------------------------------------------------
    | Write operations — tighter rate limit than plain page reads.
    |----------------------------------------------------------------------
    */
    Route::middleware('throttle:write')->group(function () {

        Route::post('/admin/my-account/{id}/update-avatar', [AccountController::class, 'updateAvatar'])->name('avatar-update');
        Route::post('/admin/my-security', [SecurityController::class, 'store'])->name('change.password');
        Route::post('/admin/my-security/logout-other-devices', [SecurityController::class, 'logoutOtherDevices'])
            ->name('security.logout-other-devices');

        // --- Settings (Superadmin) ---
        Route::middleware('can:view_resources')->group(function () {
            Route::post('/admin/settings/update', [SettingController::class, 'update'])->name('settings.update');
            Route::post('/admin/settings/regenerate-branding', [SettingController::class, 'regenerateBranding'])
                ->name('settings.branding');

            Route::post('/admin/roles/generate-permissions', [RoleController::class, 'generatePermissions'])->name('roles.generate');
            Route::post('/admin/users/mass-delete', [UserController::class, 'massDelete'])->name('users.mass-delete');
            Route::post('/admin/users/{id}/ban', [UserController::class, 'ban'])->name('users.ban');
            Route::post('/admin/users/{id}/unban', [UserController::class, 'unban'])->name('users.unban');
            Route::post('/admin/roles/mass-delete', [RoleController::class, 'massDelete'])->name('roles.mass-delete');
        });
    });

    // --- Profile & security pages (resource controllers keep their own verbs) ---
    // Hanya method yang benar-benar ada; sisanya dulu terdaftar dan berujung 500.
    Route::resource('/admin/my-profile', ProfileController::class)->only(['index', 'edit', 'update']);
    Route::resource('/admin/my-security', SecurityController::class)->only(['index', 'edit', 'update']);

    // --- Settings page (read) ---
    Route::get('/admin/settings', [SettingController::class, 'index'])
        ->middleware('can:view_resources')
        ->name('settings.index');

    /*
    |----------------------------------------------------------------------
    | Layanan SaaS — Superadmin only.
    |----------------------------------------------------------------------
    */
    Route::middleware('role:Superadmin|superadmin')->prefix('admin/saas')->name('admin.saas.')->group(function () {
        Route::get('/pendaftaran', [RegistrationAdminController::class, 'index'])->name('registrations.index');
        Route::get('/pendaftaran/baru', [RegistrationAdminController::class, 'create'])->name('registrations.create');
        Route::get('/pendaftaran/{registration}', [RegistrationAdminController::class, 'show'])->name('registrations.show');

        Route::get('/tim', [TeamAdminController::class, 'index'])->name('teams.index');
        Route::get('/tim/{team:id}', [TeamAdminController::class, 'show'])->name('teams.show');
        Route::get('/bukti/{payment}', [TeamAdminController::class, 'proof'])->name('payments.proof');

        Route::get('/pembayaran', [BillingSettingController::class, 'index'])->name('billing.index');

        Route::middleware('throttle:write')->group(function () {
            Route::post('/pendaftaran', [RegistrationAdminController::class, 'store'])->name('registrations.store');
            Route::post('/pendaftaran/{registration}/setujui', [RegistrationAdminController::class, 'approve'])->name('registrations.approve');
            Route::post('/pendaftaran/{registration}/tolak', [RegistrationAdminController::class, 'reject'])->name('registrations.reject');

            Route::post('/tim/{team:id}/perpanjang', [TeamAdminController::class, 'renew'])->name('teams.renew');
            Route::patch('/tim/{team:id}/status', [TeamAdminController::class, 'updateStatus'])->name('teams.status');

            Route::put('/pembayaran/paket', [BillingSettingController::class, 'updatePlans'])->name('billing.plans');
            Route::put('/pembayaran/whatsapp', [BillingSettingController::class, 'updateWhatsapp'])->name('billing.whatsapp');
            Route::post('/pembayaran/rekening', [BillingSettingController::class, 'storeBank'])->name('billing.banks.store');
            Route::put('/pembayaran/rekening/{bank}', [BillingSettingController::class, 'updateBank'])->name('billing.banks.update');
            Route::delete('/pembayaran/rekening/{bank}', [BillingSettingController::class, 'destroyBank'])->name('billing.banks.destroy');
        });
    });

    /*
    |----------------------------------------------------------------------
    | User & role management — Superadmin only.
    |----------------------------------------------------------------------
    */
    Route::middleware('can:view_resources')->group(function () {
        Route::resource('/admin/users', UserController::class);
        Route::get('/admin/get-datauser', [UserController::class, 'getDataUsers'])->name('get-users');
        Route::get('/admin/get-user-show-log/{id}', [UserController::class, 'getLoginSession'])->name('get-user-show-log');
        Route::get('/admin/get-user-show-log-activity/{id}', [UserController::class, 'getActivity'])->name('get-user-show-log-activity');

        Route::resource('/admin/roles', RoleController::class);
        Route::get('/admin/get-datarole', [RoleController::class, 'getDataRoles'])->name('get-datarole');
    });
});

require __DIR__.'/auth.php';
