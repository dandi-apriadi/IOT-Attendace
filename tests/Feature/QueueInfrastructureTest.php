<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QueueInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_queue_jobs_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('jobs'));
    }
}
