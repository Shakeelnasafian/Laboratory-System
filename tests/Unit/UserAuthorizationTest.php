<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsLabFixtures;
use Tests\TestCase;

class UserAuthorizationTest extends TestCase
{
    use BuildsLabFixtures;
    use RefreshDatabase;

    public function test_super_admin_and_lab_admin_helpers_reflect_assigned_roles(): void
    {
        $superAdmin = $this->createLabUser(User::ROLE_SUPERADMIN, null, ['lab_id' => null]);
        $labAdmin = $this->createLabUser(User::ROLE_LAB_ADMIN, $this->createLab());

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($superAdmin->isLabAdmin());

        $this->assertFalse($labAdmin->isSuperAdmin());
        $this->assertTrue($labAdmin->isLabAdmin());
    }

    public function test_sample_collection_permissions_match_expected_roles(): void
    {
        $lab = $this->createLab();

        $this->assertTrue($this->createLabUser(User::ROLE_LAB_ADMIN, $lab)->canCollectSamples());
        $this->assertTrue($this->createLabUser(User::ROLE_LAB_INCHARGE, $lab)->canCollectSamples());
        $this->assertTrue($this->createLabUser(User::ROLE_RECEPTIONIST, $lab)->canCollectSamples());
        $this->assertFalse($this->createLabUser(User::ROLE_TECHNICIAN, $lab)->canCollectSamples());
    }

    public function test_receive_and_bench_permissions_match_expected_roles(): void
    {
        $lab = $this->createLab();

        $this->assertTrue($this->createLabUser(User::ROLE_LAB_ADMIN, $lab)->canReceiveSamples());
        $this->assertTrue($this->createLabUser(User::ROLE_LAB_INCHARGE, $lab)->canReceiveSamples());
        $this->assertTrue($this->createLabUser(User::ROLE_TECHNICIAN, $lab)->canReceiveSamples());
        $this->assertFalse($this->createLabUser(User::ROLE_RECEPTIONIST, $lab)->canReceiveSamples());

        $this->assertTrue($this->createLabUser(User::ROLE_LAB_ADMIN, $lab)->canWorkBench());
        $this->assertTrue($this->createLabUser(User::ROLE_LAB_INCHARGE, $lab)->canWorkBench());
        $this->assertTrue($this->createLabUser(User::ROLE_TECHNICIAN, $lab)->canWorkBench());
        $this->assertFalse($this->createLabUser(User::ROLE_RECEPTIONIST, $lab)->canWorkBench());
    }

    public function test_result_verification_permissions_match_expected_roles(): void
    {
        $lab = $this->createLab();

        $this->assertTrue($this->createLabUser(User::ROLE_LAB_ADMIN, $lab)->canVerifyResults());
        $this->assertTrue($this->createLabUser(User::ROLE_LAB_INCHARGE, $lab)->canVerifyResults());
        $this->assertFalse($this->createLabUser(User::ROLE_RECEPTIONIST, $lab)->canVerifyResults());
        $this->assertFalse($this->createLabUser(User::ROLE_TECHNICIAN, $lab)->canVerifyResults());

        $this->assertTrue($this->createLabUser(User::ROLE_LAB_ADMIN, $lab)->canReleaseResults());
        $this->assertTrue($this->createLabUser(User::ROLE_LAB_INCHARGE, $lab)->canReleaseResults());
        $this->assertFalse($this->createLabUser(User::ROLE_RECEPTIONIST, $lab)->canReleaseResults());
    }
}
