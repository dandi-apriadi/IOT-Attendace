<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceEnrollmentJob extends Model
{
    use HasFactory;

    protected $table = 'device_enrollment_jobs';

    protected $fillable = [
        'mahasiswa_id',
        'device_id',
        'capture_type',
        'status',
        'captured_value',
        'error_message',
        'result_payload',
        'requested_by',
        'expires_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'result_payload' => 'array',
        'expires_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
