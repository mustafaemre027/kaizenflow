<?php

namespace Tests\Unit\Enums;

use App\Enums\KaizenPriority;
use PHPUnit\Framework\TestCase;

class KaizenPriorityTest extends TestCase
{
    public function test_it_has_exactly_three_cases(): void
    {
        $cases = KaizenPriority::cases();
        $this->assertCount(3, $cases);
    }

    public function test_it_has_expected_backed_values(): void
    {
        $this->assertEquals('LOW', KaizenPriority::LOW->value);
        $this->assertEquals('MEDIUM', KaizenPriority::MEDIUM->value);
        $this->assertEquals('HIGH', KaizenPriority::HIGH->value);
    }

    public function test_it_returns_correct_labels(): void
    {
        $this->assertEquals('Düşük', KaizenPriority::LOW->label());
        $this->assertEquals('Orta', KaizenPriority::MEDIUM->label());
        $this->assertEquals('Yüksek', KaizenPriority::HIGH->label());
    }

    public function test_it_can_be_resolved_from_valid_value(): void
    {
        $this->assertEquals(KaizenPriority::LOW, KaizenPriority::from('LOW'));
        $this->assertEquals(KaizenPriority::HIGH, KaizenPriority::from('HIGH'));
    }

    public function test_it_returns_null_for_invalid_value_using_try_from(): void
    {
        $this->assertNull(KaizenPriority::tryFrom('CRITICAL'));
    }
}
