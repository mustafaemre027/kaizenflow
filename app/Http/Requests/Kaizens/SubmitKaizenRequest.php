<?php

namespace App\Http\Requests\Kaizens;

use Illuminate\Foundation\Http\FormRequest;

class SubmitKaizenRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return $user->can('submit', $this->route('kaizen'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:2000'],
            'id' => 'prohibited',
            'code' => 'prohibited',
            'creator_user_id' => 'prohibited',
            'department_id' => 'prohibited',
            'category_id' => 'prohibited',
            'assigned_user_id' => 'prohibited',
            'title' => 'prohibited',
            'current_situation' => 'prohibited',
            'proposed_situation' => 'prohibited',
            'expected_benefit' => 'prohibited',
            'actual_result' => 'prohibited',
            'realized_benefit' => 'prohibited',
            'status' => 'prohibited',
            'priority' => 'prohibited',
            'target_date' => 'prohibited',
            'submitted_at' => 'prohibited',
            'approved_at' => 'prohibited',
            'started_at' => 'prohibited',
            'completed_at' => 'prohibited',
            'rejected_at' => 'prohibited',
            'actor_user_id' => 'prohibited',
            'transition_code' => 'prohibited',
            'from_status' => 'prohibited',
            'to_status' => 'prohibited',
            'metadata' => 'prohibited',
            'created_at' => 'prohibited',
            'updated_at' => 'prohibited',
        ];
    }
}
