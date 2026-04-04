<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MataKuliah extends Model
{
    use HasFactory;

    protected $table = 'mata_kuliah';

    protected $fillable = [
        'kode_mk',
        'nama_mk',
        'sks',
        'semester_akademik_id',
    ];

    public function semesterAkademik()
    {
        return $this->belongsTo(SemesterAkademik::class, 'semester_akademik_id');
    }

    public function jadwal()
    {
        return $this->hasMany(Jadwal::class);
    }

    public function dosenAssignment()
    {
        return $this->hasOne(MataKuliahDosenAssignment::class, 'mata_kuliah_id');
    }
}
