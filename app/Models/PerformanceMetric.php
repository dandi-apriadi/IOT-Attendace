<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'endpoint',
        'query_duration_ms',
        'total_duration_ms',
        'result_count',
        'page',
        'period_month',
        'kelas_id',
        'mata_kuliah_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'query_duration_ms' => 'float',
            'total_duration_ms' => 'float',
            'result_count' => 'integer',
            'page' => 'integer',
            'kelas_id' => 'integer',
            'mata_kuliah_id' => 'integer',
            'user_id' => 'integer',
        ];
    }
}
