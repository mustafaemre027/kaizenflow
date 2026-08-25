<?php

namespace Tests\Unit\Enums;

use App\Enums\CapabilityScope;
use App\Enums\UserCapability;
use PHPUnit\Framework\TestCase;

class UserCapabilityTest extends TestCase
{
    public function test_it_contains_exactly_eleven_capabilities(): void
    {
        $cases = UserCapability::cases();
        $this->assertCount(11, $cases);

        $expectedValues = [
            'kaizen.implementation.assign',
            'kaizen.implementation.start',
            'kaizen.implementation.complete',
            'organization.view',
            'organization.manage',
            'approval_configuration.view',
            'approval_configuration.manage',
            'authorization.manage',
            'kaizen.opex_review',
            'kaizen.department_approve',
            'kaizen.board_approve',
        ];

        $actualValues = array_map(fn ($case) => $case->value, $cases);
        sort($expectedValues);
        sort($actualValues);

        $this->assertEquals($expectedValues, $actualValues);
    }

    public function test_kaizen_implementation_capabilities_return_department_scope(): void
    {
        $this->assertEquals(CapabilityScope::DEPARTMENT, UserCapability::KAIZEN_IMPLEMENTATION_ASSIGN->scope());
        $this->assertEquals(CapabilityScope::DEPARTMENT, UserCapability::KAIZEN_IMPLEMENTATION_START->scope());
        $this->assertEquals(CapabilityScope::DEPARTMENT, UserCapability::KAIZEN_IMPLEMENTATION_COMPLETE->scope());
    }

    public function test_management_capabilities_return_system_scope(): void
    {
        $this->assertEquals(CapabilityScope::SYSTEM, UserCapability::from('organization.view')->scope());
        $this->assertEquals(CapabilityScope::SYSTEM, UserCapability::from('organization.manage')->scope());
        $this->assertEquals(CapabilityScope::SYSTEM, UserCapability::from('approval_configuration.view')->scope());
        $this->assertEquals(CapabilityScope::SYSTEM, UserCapability::from('approval_configuration.manage')->scope());
        $this->assertEquals(CapabilityScope::SYSTEM, UserCapability::from('authorization.manage')->scope());
    }
}
