<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // 1. Menampilkan Halaman Login
    public function showLogin()
    {
        return view('login');
    }

    // 2. Memproses Validasi Login
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Cari user berdasarkan username di database asli
        $user = DB::table('users')->where('username', $request->username)->first();

        // Cek apakah user ada dan password-nya cocok (pake Hash bcryt bawaan seeder)
        if ($user && Hash::check($request->password, $user->password)) {
            // Login berhasil, simpan ID user dan nama ke dalam session pencatat
            session([
                'logged_in' => true,
                'user_id' => $user->id,
                'username' => $user->username,
                'nama_lengkap' => $user->nama_lengkap
            ]);

            return redirect('/')->with('welcome', 'Selamat datang kembali, ' . $user->nama_lengkap);
        }

        // Jika salah, balikkan ke login dengan error
        return redirect()->back()->withErrors(['msg' => 'Username atau Password salah, bray!']);
    }

    // 3. Memproses Logout
    public function logout()
    {
        session()->flush(); // Hapus semua data session login
        return redirect('/login')->with('success', 'Berhasil logout!');
    }
}