<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Controller dasar. Berisi helper bersama untuk otorisasi kepemilikan data.
 */
abstract class Controller
{
    /**
     * Pastikan {id} pada route "akun saya" adalah milik user yang sedang login.
     *
     * Mencegah IDOR: tanpa pemeriksaan ini user biasa dapat mengubah profil,
     * avatar, email, atau password user lain hanya dengan mengganti ID di URL.
     *
     * @param  int|string  $id  ID dari parameter route.
     * @return User User yang sedang login.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException 403 bila bukan akun sendiri.
     */
    protected function ownAccountOrFail(int|string $id): User
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null || (string) $user->getKey() !== (string) $id) {
            Log::channel('security')->warning('Akses ditolak: percobaan mengubah akun lain', [
                'user_id' => $user?->getKey(),
                'target_id' => $id,
                'path' => request()->path(),
                'ip' => request()->ip(),
            ]);

            abort(403, 'Anda hanya dapat mengubah akun Anda sendiri.');
        }

        return $user;
    }

    /**
     * Pesan exception untuk respon JSON: detail hanya ditampilkan saat APP_DEBUG=true.
     */
    protected function debugMessage(\Throwable $e): ?string
    {
        return config('app.debug') ? $e->getMessage() : null;
    }
}
