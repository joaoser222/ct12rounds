<?php

namespace Tests\Unit;

use App\Jobs\SyncFullGatewayAccountJob;
use App\Jobs\SyncGatewayDataJob;
use Tests\TestCase;

class GatewaySyncJobTimeoutTest extends TestCase
{
    public function test_retry_after_outlives_every_sync_job_timeout(): void
    {
        $retryAfter = (int) config('queue.connections.database.retry_after');

        $this->assertGreaterThan(
            (new SyncGatewayDataJob([], 'customers', 'owner'))->timeout,
            $retryAfter,
            'retry_after must exceed the job timeout, otherwise the job reservation expires and a second worker runs the same job.',
        );

        $this->assertGreaterThan(
            (new SyncFullGatewayAccountJob(1, 'owner'))->timeout,
            $retryAfter,
            'retry_after must exceed the job timeout, otherwise the job reservation expires and a second worker runs the same job.',
        );
    }

    public function test_lock_ttl_outlives_every_sync_job_timeout(): void
    {
        $this->assertGreaterThan(
            (new SyncFullGatewayAccountJob(1, 'owner'))->timeout,
            SyncGatewayDataJob::LOCK_TTL,
            'The sync lock must not expire while a sync job is still running, otherwise a duplicate sync is dispatched.',
        );
    }
}