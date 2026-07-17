<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'keterangan',
        'status',
        'bukti_nota',
        'catatan_reject',
    ];

    protected $casts = [
        'tanggal_realisasi' => 'date',
        'nominal_realisasi' => 'integer',
    ];

    /**
     * Kegiatan yang berhubungan dengan realisasi ini.
     */
    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    /**
     * User yang menginput realisasi ini.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}