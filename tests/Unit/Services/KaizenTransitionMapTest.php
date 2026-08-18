<?php

namespace Tests\Unit\Services;

use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Exceptions\InvalidKaizenTransition;
use App\Services\Kaizens\KaizenTransitionMap;
use PHPUnit\Framework\TestCase;

class KaizenTransitionMapTest extends TestCase
{
    private KaizenTransitionMap $map;

    protected function setUp(): void
    {
        parent::setUp();
        $this->map = new KaizenTransitionMap;
    }

    public static function canonicalTransitionsProvider(): array
    {
        return [
            ['TR-001', KaizenStatus::DRAFT, KaizenStatus::SUBMITTED, [UserRole::EMPLOYEE]],
            ['TR-002', KaizenStatus::REVISION_REQUESTED, KaizenStatus::SUBMITTED, [UserRole::EMPLOYEE]],
            ['TR-003', KaizenStatus::SUBMITTED, KaizenStatus::REVISION_REQUESTED, [UserRole::OPEX_SPECIALIST]],
            ['TR-004', KaizenStatus::SUBMITTED, KaizenStatus::MANAGER_REVIEW, [UserRole::OPEX_SPECIALIST]],
            ['TR-005', KaizenStatus::SUBMITTED, KaizenStatus::REJECTED, [UserRole::OPEX_SPECIALIST]],
            ['TR-006', KaizenStatus::MANAGER_REVIEW, KaizenStatus::APPROVED, [UserRole::MANAGER]],
            ['TR-007', KaizenStatus::MANAGER_REVIEW, KaizenStatus::REJECTED, [UserRole::MANAGER]],
            ['TR-008', KaizenStatus::APPROVED, KaizenStatus::IN_PROGRESS, [UserRole::OPEX_SPECIALIST, UserRole::MANAGER]],
            ['TR-009', KaizenStatus::IN_PROGRESS, KaizenStatus::COMPLETED, [UserRole::OPEX_SPECIALIST, UserRole::MANAGER]],
        ];
    }

    public function test_it_defines_all_nine_canonical_transitions(): void
    {
        foreach (self::canonicalTransitionsProvider() as $transitionData) {
            [$code, $from, $to, $roles] = $transitionData;

            $this->assertTrue($this->map->isValidTransition($from, $to));
            $this->assertEquals($code, $this->map->getTransitionCode($from, $to));
            $this->assertEquals($roles, $this->map->getAllowedRoles($from, $to));

            foreach ($roles as $role) {
                $this->assertTrue($this->map->canRolePerformTransition($from, $to, $role));
            }
        }
    }

    public function test_it_has_exactly_nine_transitions(): void
    {
        $this->assertCount(9, $this->map->getTransitions());
    }

    public function test_it_only_uses_eight_canonical_statuses(): void
    {
        $usedStatuses = [];
        foreach ($this->map->getTransitions() as $transition) {
            $usedStatuses[] = $transition['from']->value;
            $usedStatuses[] = $transition['to']->value;
        }

        $uniqueStatuses = array_unique($usedStatuses);

        $this->assertLessThanOrEqual(8, count($uniqueStatuses));

        foreach ($uniqueStatuses as $statusValue) {
            $this->assertNotNull(KaizenStatus::tryFrom($statusValue));
        }
    }

    public function test_opex_review_is_not_used(): void
    {
        foreach ($this->map->getTransitions() as $transition) {
            $this->assertNotEquals('OPEX_REVIEW', $transition['from']->value);
            $this->assertNotEquals('OPEX_REVIEW', $transition['to']->value);
        }
    }

    public function test_self_transitions_are_rejected(): void
    {
        foreach (KaizenStatus::cases() as $status) {
            $this->assertFalse($this->map->isValidTransition($status, $status));
            $this->assertFalse($this->map->canRolePerformTransition($status, $status, UserRole::ADMIN));
        }
    }

    public function test_undefined_transitions_are_rejected(): void
    {
        $this->assertFalse($this->map->isValidTransition(KaizenStatus::DRAFT, KaizenStatus::COMPLETED));
        $this->assertFalse($this->map->isValidTransition(KaizenStatus::REJECTED, KaizenStatus::DRAFT));
        $this->assertFalse($this->map->isValidTransition(KaizenStatus::IN_PROGRESS, KaizenStatus::DRAFT));
    }

    public function test_wrong_role_cannot_perform_transition(): void
    {
        $this->assertFalse($this->map->canRolePerformTransition(KaizenStatus::DRAFT, KaizenStatus::SUBMITTED, UserRole::ADMIN));
        $this->assertFalse($this->map->canRolePerformTransition(KaizenStatus::DRAFT, KaizenStatus::SUBMITTED, UserRole::MANAGER));

        $this->assertFalse($this->map->canRolePerformTransition(KaizenStatus::SUBMITTED, KaizenStatus::MANAGER_REVIEW, UserRole::EMPLOYEE));
    }

    public function test_terminal_statuses_have_no_next_statuses(): void
    {
        $this->assertEmpty($this->map->getAvailableNextStatuses(KaizenStatus::COMPLETED));
        $this->assertEmpty($this->map->getAvailableNextStatuses(KaizenStatus::REJECTED));
    }

    public function test_it_throws_exception_on_invalid_transition_code(): void
    {
        $this->expectException(InvalidKaizenTransition::class);
        $this->expectExceptionMessage('Invalid Kaizen transition from DRAFT to COMPLETED.');

        $this->map->getTransitionCode(KaizenStatus::DRAFT, KaizenStatus::COMPLETED);
    }

    public function test_it_throws_exception_on_invalid_transition_roles(): void
    {
        $this->expectException(InvalidKaizenTransition::class);
        $this->map->getAllowedRoles(KaizenStatus::DRAFT, KaizenStatus::COMPLETED);
    }
}
