<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kegiatan extends Model
{
    protected $table = 'kegiatan';

    protected $fillable = [
        'program_id',
        'kode_kegiatan',
        'nama_kegiatan',
        'pagu_anggaran',
        'target_serapan_persen',
    ];

    protected $casts = [
        'pagu_anggaran' => 'integer',
        'target_serapan_persen' => 'float',
    ];

    /**
     * Program induk dari kegiatan ini.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Semua realisasi yang terkait dengan kegiatan ini.
     */
    public function realisasis(): HasMany
    {
        return $this->hasMany(Realisasi::class);
    }
}
