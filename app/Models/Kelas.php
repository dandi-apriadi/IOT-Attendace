<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas';

    protected $fillable = [
        'nama_kelas',
        'semester_level',
        'next_kelas_id',
    ];

    public function mahasiswa()
    {
        return $this->hasMany(Mahasiswa::class);
    }

    public function nextKelas()
    {
        return $this->belongsTo(self::class, 'next_kelas_id');
    }

    public function previousKelas()
    {
        return $this->hasMany(self::class, 'next_kelas_id');
    }

    public function jadwal()
    {
        return $this->hasMany(Jadwal::class);
    }
}
