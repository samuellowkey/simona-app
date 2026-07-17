<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Mengatasi potensi kegagalan testing akibat dependensi tabel audit_logs
        try {
            if (Auth::check()) {
                DB::table('audit_logs')->insert([
                    'user_id'    => Auth::id(),
                    'aktivitas'  => 'User berhasil masuk ke dalam sistem (Login)',
                    'ip_address' => $request->ip(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            // Log eror ke file internal agar tidak menghentikan proses login pengguna
            Log::error('Gagal mencatat audit log login: ' . $e->getMessage());
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Catat aktivitas logout ke audit log sebelum session diinvalidate
        try {
            if (Auth::check()) {
                DB::table('audit_logs')->insert([
                    'user_id'    => Auth::id(),
                    'aktivitas'  => 'User keluar dari sistem (Logout)',
                    'ip_address' => $request->ip(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Gagal mencatat audit log logout: ' . $e->getMessage());
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}