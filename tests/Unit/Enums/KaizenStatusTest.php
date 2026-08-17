<?php

namespace Tests\Unit\Enums;

use App\Enums\KaizenStatus;
use PHPUnit\Framework\TestCase;
use ValueError;

class KaizenStatusTest extends TestCase
{
    public function test_it_has_exactly_eight_cases(): void
    {
        $this->assertCount(8, KaizenStatus::cases());
    }

    public function test_it_has_expected_backed_values_and_is_unique_without_opex_review(): void
    {
        $expected = [
            'DRAFT',
            'SUBMITTED',
            'REVISION_REQUESTED',
            'MANAGER_REVIEW',
            'APPROVED',
            'IN_PROGRESS',
            'COMPLETED',
            'REJECTED',
        ];
        $actual = array_column(KaizenStatus::cases(), 'value');

        $this->assertSame($expected, $actual);
        $this->assertCount(count(array_unique($actual)), $actual);
        $this->assertNotContains('OPEX_REVIEW', $actual);
    }

    public function test_it_returns_correct_labels(): void
    {
        $this->assertSame('Taslak', KaizenStatus::DRAFT->label());
        $this->assertSame('Gönderildi', KaizenStatus::SUBMITTED->label());
        $this->assertSame('Revizyon İstendi', KaizenStatus::REVISION_REQUESTED->label());
        $this->assertSame('Yönetici İncelemesi', KaizenStatus::MANAGER_REVIEW->label());
        $this->assertSame('Onaylandı', KaizenStatus::APPROVED->label());
        $this->assertSame('Uygulamada', KaizenStatus::IN_PROGRESS->label());
        $this->assertSame('Tamamlandı', KaizenStatus::COMPLETED->label());
        $this->assertSame('Reddedildi', KaizenStatus::REJECTED->label());
    }

    public function test_it_identifies_terminal_statuses(): void
    {
        $this->assertTrue(KaizenStatus::COMPLETED->isTerminal());
        $this->assertTrue(KaizenStatus::REJECTED->isTerminal());

        $this->assertFalse(KaizenStatus::DRAFT->isTerminal());
        $this->assertFalse(KaizenStatus::SUBMITTED->isTerminal());
        $this->assertFalse(KaizenStatus::REVISION_REQUESTED->isTerminal());
        $this->assertFalse(KaizenStatus::MANAGER_REVIEW->isTerminal());
        $this->assertFalse(KaizenStatus::APPROVED->isTerminal());
        $this->assertFalse(KaizenStatus::IN_PROGRESS->isTerminal());
    }

    public function test_it_can_be_resolved_from_valid_value(): void
    {
        $this->assertSame(KaizenStatus::DRAFT, KaizenStatus::from('DRAFT'));
    }

    public function test_it_throws_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);
        KaizenStatus::from('INVALID_STATUS');
    }
}
