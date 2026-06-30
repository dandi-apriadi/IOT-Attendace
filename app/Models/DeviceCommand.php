<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceCommand extends Model
{
    use HasFactory;

    protected $table = 'device_commands';

    protected $fillable = [
        'device_id',
        'type',
        'payload',
        'status',
        'dispatch_attempts',
        'result',
        'error',
        'requested_by',
        'dispatched_at',
        'completed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
        'dispatch_attempts' => 'integer',
        'dispatched_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
