<?php

namespace App\Http\Controllers\Auth;

use App\Application\Auth\Contracts\GoogleAuthProvider;
use App\Application\Auth\Services\GoogleAccountService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class GoogleAuthController extends Controller
{
    public function __construct(
        private GoogleAuthProvider $provider,
        private GoogleAccountService $accounts,
    ) {}

    public function redirect(Request $request): RedirectResponse
    {
        $request->session()->put('google_auth_intent', 'login');

        try {
            return $this->provider->redirect();

        } catch (\Throwable) {
            $request->session()->forget('google_auth_intent');

            return redirect()->route('login')->with('error', 'Masuk dengan Google gagal. Silakan coba lagi.');
        }
    }

    public function connect(Request $request): RedirectResponse
    {
        $request->session()->put('google_auth_intent', 'connect');
        $request->session()->put('google_auth_user_id', $request->user()->id);

        try {
            return $this->provider->redirect();

        } catch (\Throwable) {
            $request->session()->forget(['google_auth_intent', 'google_auth_user_id']);

            return redirect()->route('account')->with('error', 'Menghubungkan akun Google gagal. Silakan coba lagi.');
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        $intent = $request->session()->pull('google_auth_intent');
        $userId = $request->session()->pull('google_auth_user_id');

        if ($intent === 'login' && Auth::check()) {
            return redirect()->route('account')->with('error', 'Anda sudah masuk. Silakan keluar terlebih dahulu untuk masuk dengan akun Google lain.');
        }

        if ($intent !== 'login' && $intent !== 'connect') {
            return Auth::check()
                ? redirect()->route('account')->with('error', 'Sesi autentikasi Google tidak valid. Silakan coba lagi.')
                : redirect()->route('login')->with('error', 'Sesi autentikasi Google tidak valid. Silakan coba lagi.');
        }

        try {
            $identity = $this->provider->user();

            if ($intent === 'login') {
                $user = $this->accounts->loginWithGoogle($identity);

                Auth::login($user);
                $request->session()->regenerate();

                return redirect()->route('home');
            }

            $user = $request->user();

            if ($user === null || (string) $userId !== (string) $user->id) {
                return redirect()->route('login')->with('error', 'Sesi autentikasi tidak valid. Silakan masuk terlebih dahulu.');
            }

            $this->accounts->connectGoogle($user, $identity);

            return redirect()->route('settings.show')->with('success', 'Akun Google berhasil dihubungkan.');

        } catch (\Throwable) {
            return $intent === 'connect'
                ? redirect()->route('settings.show')->with('error', 'Menghubungkan akun Google gagal. Silakan coba lagi.')
                : redirect()->route('login')->with('error', 'Masuk dengan Google gagal. Silakan coba lagi.');
        }
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->canDisconnectGoogle()) {
            return redirect()->route('account')->with('error', 'Akun hanya menggunakan Google untuk masuk.');
        }

        try {
            $this->accounts->disconnectGoogle($user);

            return redirect()->route('account')->with('success', 'Hubungan Google berhasil diputus.');

        } catch (\Throwable) {
            return redirect()->route('account')->with('error', 'Gagal memutuskan hubungan Google. Silakan coba lagi.');
        }
    }
}
