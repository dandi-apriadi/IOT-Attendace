<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mahasiswa extends Model
{
    use HasFactory;

    protected $table = 'mahasiswa';

    protected $fillable = [
        'nim',
        'nama',
        'kelas_id',
        'status_akademik',
        'semester_level',
        'promotion_paused',
        'promotion_note',
        'last_promoted_at',
        'rfid_uid',
        'fingerprint_data',
        'face_model_data',
        'barcode_id',
    ];

    protected function casts(): array
    {
        return [
            'promotion_paused' => 'boolean',
            'last_promoted_at' => 'datetime',
        ];
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function absensi()
    {
        return $this->hasMany(Absensi::class);
    }

    public function corrections()
    {
        return $this->hasMany(Correction::class);
    }

    public function semesterPromotions()
    {
        return $this->hasMany(StudentSemesterPromotion::class);
    }
}
