<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentSemesterPromotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'mahasiswa_id',
        'from_kelas_id',
        'to_kelas_id',
        'from_semester_level',
        'to_semester_level',
        'mode',
        'note',
        'promoted_at',
    ];

    protected function casts(): array
    {
        return [
            'promoted_at' => 'datetime',
        ];
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function fromKelas()
    {
        return $this->belongsTo(Kelas::class, 'from_kelas_id');
    }

    public function toKelas()
    {
        return $this->belongsTo(Kelas::class, 'to_kelas_id');
    }
}
