<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogAktivitas extends Model
{
    protected $table = 'audit_logs';
    protected $fillable = ['user_name', 'aktivitas', 'deskripsi', 'ip_address'];
}
