<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Support\ActivityRecorder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    protected $providers = ['google', 'facebook', 'github', 'linkedin-openid'];

    /**
     * Redirect to social provider OAuth page.
     */
    public function redirect(string $provider)
    {
        $providerKey = $this->normalizeProvider($provider);

        // Check if provider is enabled
        if (! Setting::get("social_{$providerKey}_enabled")) {
            return redirect()->route('login')->with('error', 'Login dengan '.ucfirst($providerKey).' tidak diaktifkan.');
        }

        // Set config dynamically from DB
        $this->setProviderConfig($provider, $providerKey);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the callback from social provider.
     */
    public function callback(string $provider)
    {
        $providerKey = $this->normalizeProvider($provider);

        // Check if provider is enabled
        if (! Setting::get("social_{$providerKey}_enabled")) {
            return redirect()->route('login')->with('error', 'Login dengan '.ucfirst($providerKey).' tidak diaktifkan.');
        }

        // Set config dynamically from DB
        $this->setProviderConfig($provider, $providerKey);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Gagal autentikasi dengan '.ucfirst($providerKey).'. Silakan coba lagi.');
        }

        // Find existing user by email or social_id
        $user = User::where('email', $socialUser->getEmail())
            ->orWhere(function ($query) use ($socialUser, $providerKey) {
                $query->where('social_id', $socialUser->getId())
                    ->where('social_type', $providerKey);
            })
            ->first();

        if ($user) {
            // Update social info
            $user->update([
                'social_id' => $socialUser->getId(),
                'social_type' => $providerKey,
                'last_ip' => request()->ip(),
                'last_login' => now(),
            ]);
        } else {
            // Pendaftaran via login sosial mengikuti saklar "Izinkan registrasi".
            if (Setting::get('allow_registration', '0') !== '1') {
                Log::channel('security')->notice('Login sosial ditolak: akun belum terdaftar', [
                    'provider' => $providerKey,
                    'ip' => request()->ip(),
                ]);

                return redirect()->route('login')
                    ->with('error', 'Akun belum terdaftar. Hubungi administrator untuk mendapatkan akses.');
            }

            $user = User::create([
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                'username' => $this->generateUniqueUsername($socialUser),
                'email' => $socialUser->getEmail(),
                'password' => bcrypt(Str::random(24)),
                'social_id' => $socialUser->getId(),
                'social_type' => $providerKey,
                'avatar' => 'default.png',
                'is_active' => true,
                'last_ip' => request()->ip(),
                'last_login' => now(),
            ]);

            // Peran default dari config — JANGAN Role::first() (itu Superadmin).
            $defaultRole = (string) config('permission.default_role', '');
            if ($defaultRole !== '' && strcasecmp($defaultRole, 'superadmin') !== 0
                && \Spatie\Permission\Models\Role::where('name', $defaultRole)->exists()) {
                $user->assignRole($defaultRole);
            }
        }

        // Check if banned
        if ($user->banned_at) {
            return redirect()->route('login')->with('error', 'Akun Anda telah dibekukan. Hubungi administrator.');
        }

        // Sign in first so the audit entry is attributed to the user.
        Auth::login($user, true);
        request()->session()->regenerate();

        ActivityRecorder::loggedIn('Login via '.ucfirst($providerKey));

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Normalize provider name (linkedin-openid -> linkedin).
     */
    private function normalizeProvider(string $provider): string
    {
        return str_replace('-openid', '', $provider);
    }

    /**
     * Dynamically set socialite config from database.
     */
    private function setProviderConfig(string $driver, string $providerKey): void
    {
        $clientId = Setting::get("social_{$providerKey}_client_id", '');
        $clientSecret = (string) Setting::get("social_{$providerKey}_client_secret", '');

        // Secret disimpan terenkripsi; nilai lama (plaintext) tetap didukung.
        try {
            $clientSecret = $clientSecret !== '' ? \Illuminate\Support\Facades\Crypt::decryptString($clientSecret) : '';
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            // plaintext lama — biarkan apa adanya
        }
        $redirectUrl = url("/admin/auth/{$driver}/callback");

        config([
            "services.{$driver}.client_id" => $clientId,
            "services.{$driver}.client_secret" => $clientSecret,
            "services.{$driver}.redirect" => $redirectUrl,
        ]);
    }

    /**
     * Generate a unique username from social user data.
     */
    private function generateUniqueUsername($socialUser): string
    {
        // Str::slug() bisa menghasilkan string kosong, jadi pakai ?: bukan ??.
        $base = $socialUser->getNickname()
            ?: Str::slug(explode('@', (string) $socialUser->getEmail())[0])
            ?: 'user';

        $username = Str::slug($base);
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = Str::slug($base).$counter;
            $counter++;
        }

        return $username;
    }
}
