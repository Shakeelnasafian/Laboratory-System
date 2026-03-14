<?php

namespace Tests\Unit;

use App\Models\Sample;
use PHPUnit\Framework\TestCase;

class SampleStateTest extends TestCase
{
    public function test_is_collected_returns_true_for_collected_and_received_samples(): void
    {
        $this->assertTrue((new Sample(['status' => Sample::STATUS_COLLECTED]))->isCollected());
        $this->assertTrue((new Sample(['status' => Sample::STATUS_RECEIVED]))->isCollected());
    }

    public function test_is_collected_returns_false_for_pending_and_rejected_samples(): void
    {
        $this->assertFalse((new Sample(['status' => Sample::STATUS_PENDING]))->isCollected());
        $this->assertFalse((new Sample(['status' => Sample::STATUS_REJECTED]))->isCollected());
    }
}
