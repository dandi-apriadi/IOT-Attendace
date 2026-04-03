<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MataKuliahDosenAssignment extends Model
{
    use HasFactory;

    protected $table = 'mata_kuliah_dosen_assignments';

    protected $fillable = [
        'mata_kuliah_id',
        'user_id',
    ];

    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id');
    }

    public function dosen()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
