<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Realisasi extends Model
{
    // Mendefinisikan nama tabel secara eksplisit
    protected $table = 'realisasi';

    // Kolom yang boleh diisi lewat form
    protected $fillable = [
        'kegiatan_id',
        'tanggal_realisasi',
        'nominal_realisasi',
        'progres_fisik_persen',
        'user_id',
        'keterangan'
    ];
}