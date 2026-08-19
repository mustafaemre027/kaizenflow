<?php

namespace App\Actions\Kaizens;

use App\Enums\KaizenAttachmentContext;
use App\Models\Category;
use App\Models\Kaizen;
use App\Models\User;
use App\Services\Kaizens\KaizenAttachmentService;
use Illuminate\Support\Facades\DB;

class CreateKaizenDraftWithEvidence
{
    public function __construct(
        private readonly CreateKaizenDraft $createKaizenDraft,
        private readonly KaizenAttachmentService $attachmentService
    ) {}

    public function execute(
        User $creator,
        Category $category,
        array $kaizenData,
        array $currentSituationImages = [],
        array $proposedSituationImages = []
    ): Kaizen {
        return DB::transaction(function () use ($creator, $category, $kaizenData, $currentSituationImages, $proposedSituationImages) {

            // 1. Core Kaizen create
            $kaizen = $this->createKaizenDraft->execute($creator, $category, $kaizenData);

            // 2. Current evidence
            if (! empty($currentSituationImages)) {
                $this->attachmentService->storeMany(
                    $kaizen,
                    $creator,
                    KaizenAttachmentContext::CURRENT_SITUATION,
                    $currentSituationImages
                );
            }

            // 3. Proposed evidence
            if (! empty($proposedSituationImages)) {
                $this->attachmentService->storeMany(
                    $kaizen,
                    $creator,
                    KaizenAttachmentContext::PROPOSED_SITUATION,
                    $proposedSituationImages
                );
            }

            return $kaizen;
        });
    }
}
