<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsLabFixtures;
use Tests\TestCase;

class OrderHelpersTest extends TestCase
{
    use BuildsLabFixtures;
    use RefreshDatabase;

    public function test_order_release_helpers_are_false_without_items_or_results(): void
    {
        $lab = $this->createLab();
        $creator = $this->createLabUser(\App\Models\User::ROLE_LAB_ADMIN, $lab);
        $order = $this->createOrder($lab, $creator);

        $this->assertFalse($order->allResultsVerified());
        $this->assertFalse($order->allResultsReleased());
        $this->assertFalse($order->canReleaseReport());
        $this->assertFalse($order->canPrintReport());
    }

    public function test_order_recognizes_verified_and_released_results_correctly(): void
    {
        $lab = $this->createLab();
        $creator = $this->createLabUser(\App\Models\User::ROLE_LAB_ADMIN, $lab);
        $order = $this->createOrder($lab, $creator);
        $firstItem = $this->createOrderItem($order);
        $secondItem = $this->createOrderItem($order);

        $this->createResult($firstItem, $creator, [
            'status' => Result::STATUS_VERIFIED,
            'is_verified' => true,
        ]);
        $this->createResult($secondItem, $creator, [
            'status' => Result::STATUS_RELEASED,
            'is_verified' => true,
        ]);

        $order->refresh();

        $this->assertTrue($order->allResultsVerified());
        $this->assertFalse($order->allResultsReleased());
        $this->assertTrue($order->canReleaseReport());
        $this->assertFalse($order->canPrintReport());
    }

    public function test_order_counts_only_critical_results(): void
    {
        $lab = $this->createLab();
        $creator = $this->createLabUser(\App\Models\User::ROLE_LAB_ADMIN, $lab);
        $order = $this->createOrder($lab, $creator);

        $criticalItem = $this->createOrderItem($order);
        $normalItem = $this->createOrderItem($order);

        $this->createResult($criticalItem, $creator, [
            'flag' => Result::FLAG_CRITICAL,
            'is_abnormal' => true,
            'status' => Result::STATUS_RELEASED,
            'is_verified' => true,
        ]);
        $this->createResult($normalItem, $creator, [
            'flag' => Result::FLAG_NORMAL,
            'status' => Result::STATUS_RELEASED,
            'is_verified' => true,
        ]);

        $this->assertSame(1, $order->fresh()->criticalResultsCount());
    }

    public function test_order_number_is_generated_automatically_per_lab(): void
    {
        $lab = $this->createLab();
        $creator = $this->createLabUser(\App\Models\User::ROLE_LAB_ADMIN, $lab);
        $order = $this->createOrder($lab, $creator, null, ['order_number' => null]);

        $this->assertNotNull($order->order_number);
        $this->assertStringStartsWith('ORD-' . now()->format('Ymd') . '-', $order->order_number);
    }
}
