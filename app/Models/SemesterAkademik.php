<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SemesterAkademik extends Model
{
    use HasFactory;

    protected $table = 'semester_akademik';

    protected $fillable = [
        'nama_semester',
        'tahun_ajaran',
        'tanggal_mulai',
        'tanggal_selesai',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function jadwal()
    {
        return $this->hasMany(Jadwal::class, 'semester_akademik_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return trim($this->nama_semester . ' ' . $this->tahun_ajaran);
    }
}