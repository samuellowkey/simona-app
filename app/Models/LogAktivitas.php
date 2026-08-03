<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class LogAktivitas extends Model
{
    // Sesuaikan nama tabel jika berbeda
    protected $table = 'log_aktivitas'; 

    protected $fillable = [
        'user_id',
        'aktivitas',
        'deskripsi',
        'ip_address',
    ];

    /**
     * Helper Static untuk mencatat log otomatis
     */
    public static function catat($aktivitas, $deskripsi)
    {
        $user = Auth::user();
        $namaUser = $user ? ($user->nama_lengkap ?? $user->name ?? 'User') : 'System';

        return self::create([
            'user_id'    => Auth::id(),
            'aktivitas'  => $aktivitas,
            'deskripsi'  => "User {$namaUser} {$deskripsi}",
            'ip_address' => Request::ip() ?? '127.0.0.1',
        ]);
    }
}
