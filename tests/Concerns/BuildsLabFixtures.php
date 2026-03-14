<?php

namespace Tests\Concerns;

use App\Models\Lab;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Patient;
use App\Models\Result;
use App\Models\Sample;
use App\Models\Test as LabTest;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

trait BuildsLabFixtures
{
    protected function ensureRolesExist(): void
    {
        foreach ([
            User::ROLE_SUPERADMIN,
            User::ROLE_LAB_ADMIN,
            User::ROLE_LAB_INCHARGE,
            User::ROLE_RECEPTIONIST,
            User::ROLE_TECHNICIAN,
        ] as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }
    }

    protected function createLab(array $attributes = []): Lab
    {
        return Lab::create(array_merge([
            'name' => 'Acme Lab',
            'slug' => 'acme-lab-' . Str::lower(Str::random(6)),
            'is_active' => true,
        ], $attributes));
    }

    protected function createLabUser(string $role, ?Lab $lab = null, array $attributes = []): User
    {
        $this->ensureRolesExist();

        $user = User::factory()->create(array_merge([
            'lab_id' => $lab?->id,
            'is_active' => true,
        ], $attributes));

        $user->assignRole($role);

        return $user->fresh();
    }

    protected function createPatient(Lab $lab, array $attributes = []): Patient
    {
        return Patient::create(array_merge([
            'lab_id' => $lab->id,
            'name' => 'Test Patient',
            'phone' => '0500000000',
            'gender' => 'male',
            'age' => 32,
        ], $attributes));
    }

    protected function createTest(Lab $lab, array $attributes = []): LabTest
    {
        return LabTest::create(array_merge([
            'lab_id' => $lab->id,
            'name' => 'Hemoglobin',
            'code' => 'HB-' . Str::upper(Str::random(4)),
            'price' => 500,
            'unit' => 'g/dL',
            'normal_range' => '12-16',
            'sample_type' => 'Blood',
            'turnaround_hours' => 4,
            'is_active' => true,
        ], $attributes));
    }

    protected function createOrder(Lab $lab, User $creator, ?Patient $patient = null, array $attributes = []): Order
    {
        $patient ??= $this->createPatient($lab);

        $order = Order::create(array_merge([
            'lab_id' => $lab->id,
            'patient_id' => $patient->id,
            'created_by' => $creator->id,
            'status' => Order::STATUS_PENDING,
            'total_amount' => 500,
            'discount' => 0,
            'net_amount' => 500,
        ], $attributes));

        if (isset($attributes['created_at']) || isset($attributes['updated_at'])) {
            $order->timestamps = false;
            $order->forceFill([
                'created_at' => $attributes['created_at'] ?? $order->created_at,
                'updated_at' => $attributes['updated_at'] ?? ($attributes['created_at'] ?? $order->updated_at),
            ])->saveQuietly();
            $order->timestamps = true;
        }

        return $order->fresh();
    }

    protected function createOrderItem(Order $order, ?LabTest $test = null, array $attributes = []): OrderItem
    {
        $test ??= $this->createTest($order->lab, [
            'lab_id' => $order->lab_id,
        ]);

        return OrderItem::create(array_merge([
            'order_id' => $order->id,
            'test_id' => $test->id,
            'price' => $test->price,
            'status' => OrderItem::STATUS_PENDING,
        ], $attributes));
    }

    protected function createSample(OrderItem $item, array $attributes = []): Sample
    {
        return Sample::create(array_merge([
            'lab_id' => $item->order->lab_id,
            'order_item_id' => $item->id,
            'accession_number' => 'ACC-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
            'status' => Sample::STATUS_COLLECTED,
        ], $attributes));
    }

    protected function createResult(OrderItem $item, User $enteredBy, array $attributes = []): Result
    {
        return Result::create(array_merge([
            'order_item_id' => $item->id,
            'entered_by' => $enteredBy->id,
            'value' => '13.2',
            'unit' => $item->test->unit,
            'normal_range' => $item->test->normal_range,
            'is_abnormal' => false,
            'flag' => Result::FLAG_NORMAL,
            'status' => Result::STATUS_DRAFT,
            'is_verified' => false,
        ], $attributes));
    }
}
