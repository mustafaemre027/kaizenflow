<?php

namespace Tests\Unit\Enums;

use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;
use ValueError;

class UserRoleTest extends TestCase
{
    public function test_it_has_exactly_four_cases(): void
    {
        $this->assertCount(4, UserRole::cases());
    }

    public function test_it_has_expected_backed_values(): void
    {
        $expected = ['EMPLOYEE', 'OPEX_SPECIALIST', 'MANAGER', 'ADMIN'];
        $actual = array_column(UserRole::cases(), 'value');

        $this->assertSame($expected, $actual);
        $this->assertCount(count(array_unique($actual)), $actual);
    }

    public function test_it_returns_correct_labels(): void
    {
        $this->assertSame('Çalışan', UserRole::EMPLOYEE->label());
        $this->assertSame('OPEX Uzmanı', UserRole::OPEX_SPECIALIST->label());
        $this->assertSame('Yönetici', UserRole::MANAGER->label());
        $this->assertSame('Sistem Yöneticisi', UserRole::ADMIN->label());
    }

    public function test_it_can_be_resolved_from_valid_value(): void
    {
        $this->assertSame(UserRole::EMPLOYEE, UserRole::from('EMPLOYEE'));
    }

    public function test_it_throws_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);
        UserRole::from('INVALID_ROLE');
    }
}
