<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    protected $table = 'programs';

    protected $fillable = [
        'kode_program',
        'nama_program',
        'tahun_anggaran',
    ];

    protected $casts = [
        'tahun_anggaran' => 'integer',
    ];

    /**
     * Semua kegiatan dalam program ini.
     */
    public function kegiatans(): HasMany
    {
        return $this->hasMany(Kegiatan::class);
    }
}
