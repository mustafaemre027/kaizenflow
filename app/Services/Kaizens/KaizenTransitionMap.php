<?php

namespace App\Services\Kaizens;

use App\Enums\KaizenStatus;
use App\Enums\UserRole;
use App\Exceptions\InvalidKaizenTransition;

class KaizenTransitionMap
{
    /**
     * Define all canonical transitions as per the Kaizen Workflow documentation.
     *
     * @return array<string, array{from: KaizenStatus, to: KaizenStatus, allowed_roles: array<UserRole>}>
     */
    public function getTransitions(): array
    {
        return [
            'TR-001' => [
                'from' => KaizenStatus::DRAFT,
                'to' => KaizenStatus::SUBMITTED,
                'allowed_roles' => [UserRole::EMPLOYEE],
            ],
            'TR-002' => [
                'from' => KaizenStatus::REVISION_REQUESTED,
                'to' => KaizenStatus::SUBMITTED,
                'allowed_roles' => [UserRole::EMPLOYEE],
            ],
            'TR-003' => [
                'from' => KaizenStatus::SUBMITTED,
                'to' => KaizenStatus::REVISION_REQUESTED,
                'allowed_roles' => [UserRole::OPEX_SPECIALIST],
            ],
            'TR-004' => [
                'from' => KaizenStatus::SUBMITTED,
                'to' => KaizenStatus::MANAGER_REVIEW,
                'allowed_roles' => [UserRole::OPEX_SPECIALIST],
            ],
            'TR-005' => [
                'from' => KaizenStatus::SUBMITTED,
                'to' => KaizenStatus::REJECTED,
                'allowed_roles' => [UserRole::OPEX_SPECIALIST],
            ],
            'TR-006' => [
                'from' => KaizenStatus::MANAGER_REVIEW,
                'to' => KaizenStatus::APPROVED,
                'allowed_roles' => [UserRole::MANAGER],
            ],
            'TR-007' => [
                'from' => KaizenStatus::MANAGER_REVIEW,
                'to' => KaizenStatus::REJECTED,
                'allowed_roles' => [UserRole::MANAGER],
            ],
            'TR-008' => [
                'from' => KaizenStatus::APPROVED,
                'to' => KaizenStatus::IN_PROGRESS,
                'allowed_roles' => [UserRole::OPEX_SPECIALIST, UserRole::MANAGER],
            ],
            'TR-009' => [
                'from' => KaizenStatus::IN_PROGRESS,
                'to' => KaizenStatus::COMPLETED,
                'allowed_roles' => [UserRole::OPEX_SPECIALIST, UserRole::MANAGER],
            ],
        ];
    }

    public function isValidTransition(KaizenStatus $from, KaizenStatus $to): bool
    {
        foreach ($this->getTransitions() as $transition) {
            if ($transition['from'] === $from && $transition['to'] === $to) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws InvalidKaizenTransition
     */
    public function getTransitionCode(KaizenStatus $from, KaizenStatus $to): string
    {
        foreach ($this->getTransitions() as $code => $transition) {
            if ($transition['from'] === $from && $transition['to'] === $to) {
                return $code;
            }
        }

        throw new InvalidKaizenTransition($from, $to);
    }

    /**
     * @return array<UserRole>
     *
     * @throws InvalidKaizenTransition
     */
    public function getAllowedRoles(KaizenStatus $from, KaizenStatus $to): array
    {
        foreach ($this->getTransitions() as $transition) {
            if ($transition['from'] === $from && $transition['to'] === $to) {
                return $transition['allowed_roles'];
            }
        }

        throw new InvalidKaizenTransition($from, $to);
    }

    /**
     * @return array<KaizenStatus>
     */
    public function getAvailableNextStatuses(KaizenStatus $from): array
    {
        if ($from->isTerminal()) {
            return [];
        }

        $nextStatuses = [];

        foreach ($this->getTransitions() as $transition) {
            if ($transition['from'] === $from) {
                $nextStatuses[] = $transition['to'];
            }
        }

        return $nextStatuses;
    }

    public function canRolePerformTransition(KaizenStatus $from, KaizenStatus $to, UserRole $role): bool
    {
        if (! $this->isValidTransition($from, $to)) {
            return false;
        }

        $allowedRoles = $this->getAllowedRoles($from, $to);

        return in_array($role, $allowedRoles, true);
    }
}
