<?php

use App\Http\Controllers\Team\AuthController;
use App\Http\Controllers\Team\BillingController;
use App\Http\Controllers\Team\BusinessTripController;
use App\Http\Controllers\Team\DashboardController;
use App\Http\Controllers\Team\DispositionController;
use App\Http\Controllers\Team\EmployeeController;
use App\Http\Controllers\Team\HonorariumController;
use App\Http\Controllers\Team\IncomingLetterController;
use App\Http\Controllers\Team\MasterDataController;
use App\Http\Controllers\Team\MemberController;
use App\Http\Controllers\Team\NotificationController;
use App\Http\Controllers\Team\OutgoingLetterController;
use App\Http\Controllers\Team\OvertimeController;
use App\Http\Controllers\Team\ProfileController;
use App\Http\Controllers\Team\ReportController;
use App\Http\Controllers\Team\SearchController;
use App\Http\Controllers\Team\TeamSettingController;
use App\Http\Controllers\Team\TemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Area tim: /{team}/...
|--------------------------------------------------------------------------
| {team} = slug 4 karakter. File ini dimuat PALING AKHIR (bootstrap/app.php)
| sehingga /admin, /daftar, /verifikasi, dll. selalu menang.
|
| Middleware:
|   team.resolve   → set tim aktif (isolasi data), 404 bila slug tidak ada
|   team.member    → user wajib anggota tim ini
|   team.writable  → tim beku: hanya baca (kecuali halaman langganan)
|   can:team.*     → hak akses per peran (App\Enums\TeamRole::ABILITIES)
*/

Route::prefix('{team}')
    ->where(['team' => '[a-z2-9]{4,10}'])
    ->middleware('team.resolve')
    ->name('team.')
    ->group(function () {
        Route::middleware('guest')->group(function () {
            Route::get('/', fn () => redirect()->route('team.login'));
            Route::get('/login', [AuthController::class, 'create'])->name('login');
            Route::post('/login', [AuthController::class, 'store'])->middleware(['throttle:login', 'spam'])->name('login.store');
        });

        Route::middleware(['auth', 'team.member'])->group(function () {
            Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

            // ---- Langganan & profil: tetap bisa diakses saat tim beku ----
            Route::get('/langganan', [BillingController::class, 'index'])->can('team.billing.manage')->name('billing.index');
            Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profil', [ProfileController::class, 'update'])->middleware('throttle:password')->name('profile.update');
            Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
            Route::get('/notifikasi/{notification}', [NotificationController::class, 'open'])->name('notifications.open');
            Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.read-all');

            Route::middleware('team.writable')->group(function () {
                Route::get('/dashboard', DashboardController::class)->name('dashboard');

                // ---- Surat masuk ----
                Route::get('/surat-masuk', [IncomingLetterController::class, 'index'])->name('incoming.index');
                Route::get('/surat-masuk/data', [IncomingLetterController::class, 'data'])->name('incoming.data');
                Route::get('/surat-masuk/baru', [IncomingLetterController::class, 'create'])->can('team.letters.manage')->name('incoming.create');
                Route::post('/surat-masuk', [IncomingLetterController::class, 'store'])->can('team.letters.manage')->middleware('throttle:write')->name('incoming.store');
                Route::get('/surat-masuk/{incoming}', [IncomingLetterController::class, 'show'])->name('incoming.show');
                Route::get('/surat-masuk/{incoming}/ubah', [IncomingLetterController::class, 'edit'])->can('team.letters.manage')->name('incoming.edit');
                Route::put('/surat-masuk/{incoming}', [IncomingLetterController::class, 'update'])->can('team.letters.manage')->middleware('throttle:write')->name('incoming.update');
                Route::patch('/surat-masuk/{incoming}/status', [IncomingLetterController::class, 'status'])->can('team.letters.manage')->name('incoming.status');
                Route::delete('/surat-masuk/{incoming}', [IncomingLetterController::class, 'destroy'])->can('team.letters.delete')->name('incoming.destroy');

                // ---- Disposisi ----
                Route::post('/surat-masuk/{incoming}/disposisi', [DispositionController::class, 'store'])->can('team.dispositions.create')->middleware('throttle:write')->name('dispositions.store');
                Route::get('/disposisi', [DispositionController::class, 'index'])->name('dispositions.index');
                Route::patch('/disposisi/{disposition}', [DispositionController::class, 'update'])->middleware('throttle:write')->name('dispositions.update');
                Route::get('/disposisi/{disposition}/cetak', [DispositionController::class, 'print'])->name('dispositions.print');

                // ---- Surat keluar ----
                Route::get('/surat-keluar', [OutgoingLetterController::class, 'index'])->name('outgoing.index');
                Route::get('/surat-keluar/baru', [OutgoingLetterController::class, 'create'])->can('team.letters.manage')->name('outgoing.create');
                Route::post('/surat-keluar', [OutgoingLetterController::class, 'store'])->can('team.letters.manage')->middleware('throttle:write')->name('outgoing.store');
                Route::get('/surat-keluar/{outgoing}', [OutgoingLetterController::class, 'show'])->name('outgoing.show');
                Route::get('/surat-keluar/{outgoing}/ubah', [OutgoingLetterController::class, 'edit'])->can('team.letters.manage')->name('outgoing.edit');
                Route::put('/surat-keluar/{outgoing}', [OutgoingLetterController::class, 'update'])->can('team.letters.manage')->middleware('throttle:write')->name('outgoing.update');
                Route::post('/surat-keluar/{outgoing}/ajukan', [OutgoingLetterController::class, 'submit'])->can('team.letters.manage')->name('outgoing.submit');
                Route::post('/surat-keluar/{outgoing}/setujui', [OutgoingLetterController::class, 'approve'])->can('team.outgoing.approve')->name('outgoing.approve');
                Route::post('/surat-keluar/{outgoing}/revisi', [OutgoingLetterController::class, 'reject'])->can('team.outgoing.approve')->name('outgoing.reject');
                Route::post('/surat-keluar/{outgoing}/terkirim', [OutgoingLetterController::class, 'markSent'])->can('team.letters.manage')->name('outgoing.sent');
                Route::get('/surat-keluar/{outgoing}/pdf', [OutgoingLetterController::class, 'pdf'])->name('outgoing.pdf');
                Route::delete('/surat-keluar/{outgoing}', [OutgoingLetterController::class, 'destroy'])->can('team.letters.delete')->name('outgoing.destroy');

                // ---- Arsip, pencarian, laporan ----
                Route::get('/arsip', SearchController::class)->name('archive.index');
                Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
                Route::get('/laporan/unduh', [ReportController::class, 'download'])->name('reports.download');

                // ---- Data master ----
                Route::get('/pegawai', [EmployeeController::class, 'index'])->name('employees.index');
                Route::middleware('can:team.master.manage')->group(function () {
                    Route::post('/pegawai', [EmployeeController::class, 'store'])->name('employees.store');
                    Route::put('/pegawai/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
                    Route::delete('/pegawai/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
                    Route::post('/divisi', [EmployeeController::class, 'storeDivision'])->name('employees.divisions.store');
                    Route::put('/divisi/{division}', [EmployeeController::class, 'updateDivision'])->name('employees.divisions.update');
                    Route::delete('/divisi/{division}', [EmployeeController::class, 'destroyDivision'])->name('employees.divisions.destroy');
                });

                Route::get('/data-master', [MasterDataController::class, 'index'])->name('master.index');
                Route::middleware('can:team.master.manage')->group(function () {
                    Route::post('/data-master/{type}', [MasterDataController::class, 'store'])->name('master.store');
                    Route::put('/data-master/{type}/{id}', [MasterDataController::class, 'update'])->whereNumber('id')->name('master.update');
                    Route::delete('/data-master/{type}/{id}', [MasterDataController::class, 'destroy'])->whereNumber('id')->name('master.destroy');
                });

                Route::get('/template', [TemplateController::class, 'index'])->name('templates.index');
                Route::get('/template/{template}/pratinjau', [TemplateController::class, 'preview'])->name('templates.preview');
                Route::middleware('can:team.templates.manage')->group(function () {
                    Route::get('/template/baru', [TemplateController::class, 'create'])->name('templates.create');
                    Route::post('/template', [TemplateController::class, 'store'])->name('templates.store');
                    Route::post('/template/{template}/salin', [TemplateController::class, 'duplicate'])->name('templates.duplicate');
                    Route::get('/template/{template}/ubah', [TemplateController::class, 'edit'])->name('templates.edit');
                    Route::put('/template/{template}', [TemplateController::class, 'update'])->name('templates.update');
                    Route::delete('/template/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');
                });

                // ---- Tim ----
                Route::middleware('can:team.members.manage')->group(function () {
                    Route::get('/anggota', [MemberController::class, 'index'])->name('members.index');
                    Route::post('/anggota', [MemberController::class, 'store'])->middleware('throttle:write')->name('members.store');
                    Route::put('/anggota/{member}', [MemberController::class, 'update'])->name('members.update');
                    Route::put('/anggota/{member}/password', [MemberController::class, 'resetPassword'])->name('members.password');
                    Route::delete('/anggota/{member}', [MemberController::class, 'destroy'])->name('members.destroy');
                });

                Route::middleware('can:team.settings.manage')->group(function () {
                    Route::get('/pengaturan', [TeamSettingController::class, 'edit'])->name('settings.edit');
                    Route::put('/pengaturan', [TeamSettingController::class, 'update'])->name('settings.update');
                });
                Route::put('/pengaturan/modul', [TeamSettingController::class, 'modules'])->can('team.modules.manage')->name('settings.modules');

                // ---- Modul opsional (dibuka/dikunci owner) ----
                foreach ([
                    ['lembur', 'lembur', OvertimeController::class, 'overtimes', 'overtime'],
                    ['perjadin', 'perjalanan-dinas', BusinessTripController::class, 'trips', 'trip'],
                    ['honorarium', 'honorarium', HonorariumController::class, 'honoraria', 'honorarium'],
                ] as [$module, $path, $controller, $name, $param]) {
                    Route::middleware('team.module:'.$module)->group(function () use ($path, $controller, $name, $param) {
                        Route::get("/{$path}", [$controller, 'index'])->name("{$name}.index");
                        Route::middleware('can:team.optional.manage')->group(function () use ($path, $controller, $name, $param) {
                            Route::post("/{$path}", [$controller, 'store'])->name("{$name}.store");
                            Route::patch("/{$path}/{{$param}}", [$controller, 'update'])->name("{$name}.update");
                            Route::delete("/{$path}/{{$param}}", [$controller, 'destroy'])->name("{$name}.destroy");
                        });
                    });
                }
            });
        });
    });
