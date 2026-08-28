<?php

namespace App\Actions\Kaizens;

use App\Enums\KaizenStatus;
use App\Models\Category;
use App\Models\Kaizen;
use App\Models\User;
use App\Services\KaizenCodeGenerator;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateKaizenDraft
{
    public function __construct(private readonly KaizenCodeGenerator $codeGenerator) {}

    public function execute(User $creator, Category $category, array $attributes): Kaizen
    {
        if (! $creator->is_active) {
            throw new DomainException('Inactive users cannot create a Kaizen.');
        }

        if (! $creator->department_id) {
            throw new DomainException('User must belong to a department to create a Kaizen.');
        }

        if (! $creator->department || ! $creator->department->is_active) {
            throw new DomainException('User department must be active to create a Kaizen.');
        }

        if (! $category->is_active) {
            throw new DomainException('Category must be active to create a Kaizen.');
        }

        return DB::transaction(function () use ($creator, $category, $attributes) {
            $safeAttributes = Arr::only($attributes, [
                'title',
                'current_situation',
                'proposed_situation',
                'priority',
                'target_date',
            ]);

            $kaizen = new Kaizen;
            $kaizen->fill($safeAttributes);

            $kaizen->creator_user_id = $creator->id;
            $kaizen->department_id = $creator->department_id;
            $kaizen->category_id = $category->id;
            $kaizen->assigned_user_id = null;
            $kaizen->status = KaizenStatus::DRAFT;
            $kaizen->actual_result = null;
            $kaizen->realized_benefit = null;
            $kaizen->submitted_at = null;
            $kaizen->approved_at = null;
            $kaizen->started_at = null;
            $kaizen->completed_at = null;
            $kaizen->rejected_at = null;

            // Generate temporary UUID to satisfy unique constraint before we have an ID
            $kaizen->code = Str::uuid()->toString();

            $kaizen->save();

            // Replace placeholder with canonical code and update
            $kaizen->code = $this->codeGenerator->generate($kaizen);
            $kaizen->save();

            return $kaizen->refresh();
        });
    }
}
