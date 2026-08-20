<?php

namespace Tests\Feature\Workflow;

use App\Models\ApprovalGroup;
use App\Models\ApprovalGroupMember;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalGroupModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_prevents_duplicate_memberships()
    {
        $group = ApprovalGroup::factory()->create();
        $user = User::factory()->create();

        ApprovalGroupMember::factory()->create([
            'approval_group_id' => $group->id,
            'user_id' => $user->id,
        ]);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Integrity constraint violation');

        ApprovalGroupMember::factory()->create([
            'approval_group_id' => $group->id,
            'user_id' => $user->id,
        ]);
    }
}
