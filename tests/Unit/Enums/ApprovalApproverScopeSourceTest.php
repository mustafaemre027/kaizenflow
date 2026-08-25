<?php

namespace Tests\Unit\Enums;

use App\Enums\ApprovalApproverScopeSource;
use PHPUnit\Framework\TestCase;

class ApprovalApproverScopeSourceTest extends TestCase
{
    public function test_it_has_expected_cases()
    {
        $this->assertCount(2, ApprovalApproverScopeSource::cases());
        $this->assertEquals('SYSTEM', ApprovalApproverScopeSource::SYSTEM->value);
        $this->assertEquals('KAIZEN_DEPARTMENT', ApprovalApproverScopeSource::KAIZEN_DEPARTMENT->value);
    }
}
