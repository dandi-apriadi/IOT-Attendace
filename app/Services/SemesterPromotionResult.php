<?php

namespace App\Services;

use Illuminate\Support\Collection;

class SemesterPromotionResult
{
    public function __construct(
        public Collection $eligible,
        public Collection $blocked,
        public int $promoted = 0,
    ) {
    }

    public function totalReviewed(): int
    {
        return $this->eligible->count() + $this->blocked->count();
    }
}
