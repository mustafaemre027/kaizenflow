<?php

namespace Tests\Unit\Enums;

use App\Enums\ApproverResolutionMode;
use PHPUnit\Framework\TestCase;

class ApproverResolutionModeTest extends TestCase
{
    public function test_it_has_expected_cases()
    {
        $this->assertCount(2, ApproverResolutionMode::cases());
        $this->assertEquals('LEGACY_GROUP', ApproverResolutionMode::LEGACY_GROUP->value);
        $this->assertEquals('CAPABILITY_RULE', ApproverResolutionMode::CAPABILITY_RULE->value);
    }
}
