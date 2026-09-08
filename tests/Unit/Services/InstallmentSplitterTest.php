<?php

namespace Tests\Unit\Services;

use App\Services\Billing\InstallmentSplitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallmentSplitterTest extends TestCase
{
    use RefreshDatabase;

    private InstallmentSplitter $splitter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->splitter = app(InstallmentSplitter::class);
    }

    public function test_splits_evenly(): void
    {
        $result = $this->splitter->split(90.0, 3);

        $this->assertCount(3, $result);
        $this->assertEquals(30.0, $result[0]);
        $this->assertEquals(30.0, $result[1]);
        $this->assertEquals(30.0, $result[2]);
        $this->assertEqualsWithDelta(90.0, array_sum($result), 0.001);
    }

    public function test_handles_single_installment(): void
    {
        $result = $this->splitter->split(150.0, 1);

        $this->assertCount(1, $result);
        $this->assertEquals(150.0, $result[0]);
    }

    public function test_distributes_cents_remainder(): void
    {
        $result = $this->splitter->split(100.01, 3);

        $this->assertCount(3, $result);
        $this->assertEqualsWithDelta(100.01, array_sum($result), 0.001);
        $this->assertGreaterThan($result[2], $result[0]);
    }

    public function test_handles_large_values(): void
    {
        $result = $this->splitter->split(10000.00, 12);

        $this->assertCount(12, $result);
        $this->assertEqualsWithDelta(10000.0, array_sum($result), 0.001);
    }

    public function test_splits_into_two(): void
    {
        $result = $this->splitter->split(100.0, 2);

        $this->assertCount(2, $result);
        $this->assertEquals(50.0, $result[0]);
        $this->assertEquals(50.0, $result[1]);
    }

    public function test_handles_fractional_amount(): void
    {
        $result = $this->splitter->split(33.33, 3);

        $this->assertCount(3, $result);
        $this->assertEqualsWithDelta(33.33, array_sum($result), 0.001);
    }
}
