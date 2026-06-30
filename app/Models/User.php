<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ZKTECO_DOSEN_UID_OFFSET = 50000;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'zk_uid',
        'fingerprint_data',
        'fingerprint_synced_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'fingerprint_data' => 'array',
            'fingerprint_synced_at' => 'datetime',
        ];
    }

    public function zktecoUid(): int
    {
        return (int) ($this->zk_uid ?: self::ZKTECO_DOSEN_UID_OFFSET + (int) $this->id);
    }

    public function corrections()
    {
        return $this->hasMany(Correction::class);
    }

    public function approvedCorrections()
    {
        return $this->hasMany(Correction::class, 'approved_by');
    }
}
