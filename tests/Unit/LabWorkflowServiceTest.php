<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Result;
use App\Models\Sample;
use App\Models\User;
use App\Services\LabWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsLabFixtures;
use Tests\TestCase;

class LabWorkflowServiceTest extends TestCase
{
    use BuildsLabFixtures;
    use RefreshDatabase;

    protected LabWorkflowService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LabWorkflowService::class);
        $this->ensureRolesExist();
    }

    public function test_calculate_due_at_uses_order_created_at_and_test_turnaround(): void
    {
        $lab = $this->createLab();
        $creator = $this->createLabUser(User::ROLE_RECEPTIONIST, $lab);
        $order = $this->createOrder($lab, $creator, null, ['created_at' => now()->subHours(2)]);
        $test = $this->createTest($lab, ['turnaround_hours' => 6]);
        $item = $this->createOrderItem($order, $test);

        $dueAt = $this->service->calculateDueAt($item->fresh());

        $this->assertTrue($order->created_at->copy()->addHours(6)->equalTo($dueAt));
    }

    public function test_collect_sample_uses_test_defaults_and_updates_order_status(): void
    {
        $lab = $this->createLab();
        $collector = $this->createLabUser(User::ROLE_RECEPTIONIST, $lab);
        $order = $this->createOrder($lab, $collector);
        $test = $this->createTest($lab, ['sample_type' => 'Serum', 'turnaround_hours' => 8]);
        $item = $this->createOrderItem($order, $test);

        $sample = $this->service->collectSample($item->fresh(), $collector, [
            'sample_type' => '',
            'container' => '',
        ]);

        $item->refresh();
        $order->refresh();

        $this->assertSame(Sample::STATUS_COLLECTED, $sample->status);
        $this->assertSame('Serum', $sample->sample_type);
        $this->assertNull($sample->container);
        $this->assertSame(OrderItem::STATUS_SAMPLE_COLLECTED, $item->status);
        $this->assertSame(Order::STATUS_SAMPLE_COLLECTED, $order->status);
        $this->assertNotNull($item->due_at);
        $this->assertStringStartsWith('ACC-' . now()->format('Ymd') . '-', $sample->accession_number);
    }

    public function test_collect_sample_marks_recollection_on_existing_rejected_sample(): void
    {
        $lab = $this->createLab();
        $collector = $this->createLabUser(User::ROLE_RECEPTIONIST, $lab);
        $order = $this->createOrder($lab, $collector);
        $item = $this->createOrderItem($order);
        $existingSample = $this->createSample($item->fresh('order'), [
            'status' => Sample::STATUS_REJECTED,
            'accession_number' => 'ACC-' . now()->format('Ymd') . '-111',
            'rejection_reason' => 'Insufficient quantity',
        ]);

        $recollected = $this->service->collectSample($item->fresh(), $collector, [
            'sample_type' => 'Blood',
            'container' => 'EDTA',
        ]);

        $this->assertSame($existingSample->id, $recollected->id);
        $this->assertTrue($recollected->is_recollection);
        $this->assertSame('ACC-' . now()->format('Ymd') . '-111', $recollected->accession_number);
        $this->assertNull($recollected->rejection_reason);
    }

    public function test_receive_sample_rejects_non_collected_samples(): void
    {
        $lab = $this->createLab();
        $technician = $this->createLabUser(User::ROLE_TECHNICIAN, $lab);
        $order = $this->createOrder($lab, $this->createLabUser(User::ROLE_RECEPTIONIST, $lab));
        $item = $this->createOrderItem($order);
        $sample = $this->createSample($item->fresh('order'), ['status' => Sample::STATUS_PENDING]);

        try {
            $this->service->receiveSample($sample, $technician);
            $this->fail('Expected validation exception for non-collected sample.');
        } catch (ValidationException $exception) {
            $this->assertSame('Only collected samples can be received.', $exception->errors()['sample'][0]);
        }
    }

    public function test_reject_sample_resets_processing_fields_on_order_item(): void
    {
        $lab = $this->createLab();
        $receptionist = $this->createLabUser(User::ROLE_RECEPTIONIST, $lab);
        $technician = $this->createLabUser(User::ROLE_TECHNICIAN, $lab);
        $order = $this->createOrder($lab, $receptionist);
        $item = $this->createOrderItem($order, null, [
            'status' => OrderItem::STATUS_PROCESSING,
            'assigned_to' => $technician->id,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'processing_notes' => 'Under analysis',
        ]);
        $sample = $this->createSample($item->fresh('order'), [
            'status' => Sample::STATUS_RECEIVED,
            'received_by' => $technician->id,
            'received_at' => now()->subMinutes(30),
        ]);

        $this->service->rejectSample($sample->fresh(), 'Hemolyzed sample');

        $item->refresh();
        $sample->refresh();
        $order->refresh();

        $this->assertSame(Sample::STATUS_REJECTED, $sample->status);
        $this->assertSame('Hemolyzed sample', $sample->rejection_reason);
        $this->assertSame(OrderItem::STATUS_PENDING, $item->status);
        $this->assertNull($item->assigned_to);
        $this->assertNull($item->started_at);
        $this->assertNull($item->completed_at);
        $this->assertNull($item->processing_notes);
        $this->assertSame(Order::STATUS_PENDING, $order->status);
    }

    public function test_start_processing_requires_a_collected_sample(): void
    {
        $lab = $this->createLab();
        $technician = $this->createLabUser(User::ROLE_TECHNICIAN, $lab);
        $order = $this->createOrder($lab, $this->createLabUser(User::ROLE_RECEPTIONIST, $lab));
        $item = $this->createOrderItem($order);

        try {
            $this->service->startProcessing($item->fresh(), $technician);
            $this->fail('Expected validation exception when starting without a collected sample.');
        } catch (ValidationException $exception) {
            $this->assertSame('A collected sample is required before processing can start.', $exception->errors()['sample'][0]);
        }
    }

    public function test_save_result_requires_remarks_for_critical_values(): void
    {
        $lab = $this->createLab();
        $technician = $this->createLabUser(User::ROLE_TECHNICIAN, $lab);
        $order = $this->createOrder($lab, $this->createLabUser(User::ROLE_RECEPTIONIST, $lab));
        $item = $this->createOrderItem($order);
        $this->createSample($item->fresh('order'), ['status' => Sample::STATUS_RECEIVED]);

        try {
            $this->service->saveResult($item->fresh(), $technician, [
                'value' => '8.1',
                'unit' => 'g/dL',
                'normal_range' => '12-16',
                'flag' => Result::FLAG_CRITICAL,
                'remarks' => '',
            ]);
            $this->fail('Expected validation exception for critical result without remarks.');
        } catch (ValidationException $exception) {
            $this->assertSame('Critical results require remarks.', $exception->errors()['remarks'][0]);
        }
    }

    public function test_save_result_records_a_revision_when_editing_existing_result(): void
    {
        $lab = $this->createLab();
        $technician = $this->createLabUser(User::ROLE_TECHNICIAN, $lab);
        $order = $this->createOrder($lab, $this->createLabUser(User::ROLE_RECEPTIONIST, $lab));
        $item = $this->createOrderItem($order);
        $this->createSample($item->fresh('order'), ['status' => Sample::STATUS_RECEIVED]);
        $existingResult = $this->createResult($item->fresh('test'), $technician, [
            'value' => '13.0',
            'remarks' => 'Initial reading',
            'status' => Result::STATUS_DRAFT,
        ]);

        $updatedResult = $this->service->saveResult($item->fresh(), $technician, [
            'value' => '14.2',
            'unit' => 'g/dL',
            'normal_range' => '12-16',
            'flag' => Result::FLAG_HIGH,
            'remarks' => 'Analyzer rerun',
        ]);

        $existingResult->refresh();
        $item->refresh();

        $this->assertSame($existingResult->id, $updatedResult->id);
        $this->assertSame(Result::STATUS_DRAFT, $updatedResult->status);
        $this->assertTrue($updatedResult->is_abnormal);
        $this->assertSame(OrderItem::STATUS_COMPLETED, $item->status);
        $this->assertDatabaseHas('result_revisions', [
            'result_id' => $updatedResult->id,
            'previous_value' => '13.0',
            'previous_remarks' => 'Initial reading',
            'previous_status' => Result::STATUS_DRAFT,
        ]);
    }

    public function test_verify_result_requires_draft_result_and_marks_it_verified(): void
    {
        $lab = $this->createLab();
        $incharge = $this->createLabUser(User::ROLE_LAB_INCHARGE, $lab);
        $order = $this->createOrder($lab, $this->createLabUser(User::ROLE_RECEPTIONIST, $lab));
        $item = $this->createOrderItem($order);
        $result = $this->createResult($item->fresh('test'), $incharge, [
            'status' => Result::STATUS_DRAFT,
            'is_verified' => false,
        ]);

        $this->service->verifyResult($item->fresh(), $incharge);

        $result->refresh();

        $this->assertSame(Result::STATUS_VERIFIED, $result->status);
        $this->assertTrue($result->is_verified);
        $this->assertSame($incharge->id, $result->verified_by);
        $this->assertNotNull($result->verified_at);
    }

    public function test_release_order_requires_verified_results_and_marks_critical_alerts(): void
    {
        $lab = $this->createLab();
        $incharge = $this->createLabUser(User::ROLE_LAB_INCHARGE, $lab);
        $order = $this->createOrder($lab, $this->createLabUser(User::ROLE_RECEPTIONIST, $lab));
        $item = $this->createOrderItem($order);
        $draftResult = $this->createResult($item->fresh('test'), $incharge, [
            'status' => Result::STATUS_DRAFT,
            'flag' => Result::FLAG_CRITICAL,
        ]);

        try {
            $this->service->releaseOrder($order->fresh('items.result'), $incharge);
            $this->fail('Expected validation exception for unreleased draft result.');
        } catch (ValidationException $exception) {
            $this->assertSame('All results must be verified before release.', $exception->errors()['order'][0]);
        }

        $draftResult->update([
            'status' => Result::STATUS_VERIFIED,
            'is_verified' => true,
            'verified_by' => $incharge->id,
            'verified_at' => now(),
        ]);

        $this->service->releaseOrder($order->fresh('items.result'), $incharge);

        $draftResult->refresh();
        $order->refresh();

        $this->assertSame(Result::STATUS_RELEASED, $draftResult->status);
        $this->assertSame($incharge->id, $draftResult->released_by);
        $this->assertNotNull($draftResult->released_at);
        $this->assertNotNull($draftResult->critical_alerted_at);
        $this->assertSame(Order::STATUS_COMPLETED, $order->status);
    }
}
