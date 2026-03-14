<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Lab;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Result;
use App\Models\Sample;
use App\Models\Test as LabTest;
use App\Models\TestCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoLabsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_demo_labs_and_staff_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $labs = Lab::query()
            ->whereIn('slug', DemoLabsSeeder::DEMO_LAB_SLUGS)
            ->with('users.roles')
            ->get()
            ->keyBy('slug');

        $this->assertCount(2, $labs);

        $this->assertDemoStaffAccount($labs['city-diagnostic-lab'], 'admin.city@labsystem.demo', User::ROLE_LAB_ADMIN);
        $this->assertDemoStaffAccount($labs['city-diagnostic-lab'], 'incharge.city@labsystem.demo', User::ROLE_LAB_INCHARGE);
        $this->assertDemoStaffAccount($labs['city-diagnostic-lab'], 'reception.city@labsystem.demo', User::ROLE_RECEPTIONIST);
        $this->assertDemoStaffAccount($labs['city-diagnostic-lab'], 'tech.city@labsystem.demo', User::ROLE_TECHNICIAN);

        $this->assertDemoStaffAccount($labs['prime-care-laboratory'], 'admin.prime@labsystem.demo', User::ROLE_LAB_ADMIN);
        $this->assertDemoStaffAccount($labs['prime-care-laboratory'], 'incharge.prime@labsystem.demo', User::ROLE_LAB_INCHARGE);
        $this->assertDemoStaffAccount($labs['prime-care-laboratory'], 'reception.prime@labsystem.demo', User::ROLE_RECEPTIONIST);
        $this->assertDemoStaffAccount($labs['prime-care-laboratory'], 'tech.prime@labsystem.demo', User::ROLE_TECHNICIAN);
    }

    public function test_database_seeder_populates_showcase_data_for_each_demo_lab(): void
    {
        $this->seed(DatabaseSeeder::class);

        $labs = Lab::query()
            ->whereIn('slug', DemoLabsSeeder::DEMO_LAB_SLUGS)
            ->get();

        foreach ($labs as $lab) {
            $this->assertSame(10, Patient::where('lab_id', $lab->id)->count(), "{$lab->slug} patient count mismatch.");
            $this->assertSame(6, TestCategory::where('lab_id', $lab->id)->count(), "{$lab->slug} category count mismatch.");
            $this->assertSame(10, LabTest::where('lab_id', $lab->id)->count(), "{$lab->slug} test count mismatch.");
            $this->assertSame(8, Order::where('lab_id', $lab->id)->count(), "{$lab->slug} order count mismatch.");
            $this->assertSame(8, Invoice::where('lab_id', $lab->id)->count(), "{$lab->slug} invoice count mismatch.");
            $this->assertSame(7, Sample::where('lab_id', $lab->id)->count(), "{$lab->slug} sample count mismatch.");
            $this->assertSame(3, Result::whereHas('orderItem.order', fn ($query) => $query->where('lab_id', $lab->id))->count(), "{$lab->slug} result count mismatch.");
        }

        $this->assertDatabaseHas('orders', ['order_number' => 'CIT-ORD-001']);
        $this->assertDatabaseHas('orders', ['order_number' => 'CIT-ORD-008']);
        $this->assertDatabaseHas('orders', ['order_number' => 'PRI-ORD-001']);
        $this->assertDatabaseHas('orders', ['order_number' => 'PRI-ORD-008']);
        $this->assertDatabaseHas('invoices', ['invoice_number' => 'CIT-INV-008']);
        $this->assertDatabaseHas('invoices', ['invoice_number' => 'PRI-INV-008']);
        $this->assertDatabaseHas('samples', ['accession_number' => 'CIT-ACC-008']);
        $this->assertDatabaseHas('samples', ['accession_number' => 'PRI-ACC-008']);
    }

    private function assertDemoStaffAccount(Lab $lab, string $email, string $role): void
    {
        $user = $lab->users->firstWhere('email', $email);

        $this->assertNotNull($user, "Expected {$email} to be seeded for {$lab->slug}.");
        $this->assertTrue($user->hasRole($role), "Expected {$email} to have role {$role}.");
        $this->assertTrue((bool) $user->is_active, "Expected {$email} to be active.");
    }
}
