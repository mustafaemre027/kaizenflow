<?php

namespace App\Actions\Kaizens;

use App\Enums\KaizenAttachmentContext;
use App\Models\Category;
use App\Models\Kaizen;
use App\Models\User;
use App\Services\Kaizens\KaizenAttachmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

            // Track every physical path written so we can clean up if the outer
            // transaction fails after a successful storeMany call.
            $storedPaths = [];

            try {
                // 1. Core Kaizen create
                $kaizen = $this->createKaizenDraft->execute($creator, $category, $kaizenData);

                // 2. Current evidence
                if (! empty($currentSituationImages)) {
                    $stored = $this->attachmentService->storeMany(
                        $kaizen,
                        $creator,
                        KaizenAttachmentContext::CURRENT_SITUATION,
                        $currentSituationImages
                    );
                    $storedPaths = array_merge($storedPaths, $stored->pluck('storage_path')->all());
                }

                // 3. Proposed evidence
                if (! empty($proposedSituationImages)) {
                    $stored = $this->attachmentService->storeMany(
                        $kaizen,
                        $creator,
                        KaizenAttachmentContext::PROPOSED_SITUATION,
                        $proposedSituationImages
                    );
                    $storedPaths = array_merge($storedPaths, $stored->pluck('storage_path')->all());
                }

            } catch (\Exception $e) {
                // Outer transaction will be rolled back. Compensate any physical
                // files that were written before the failure.
                $this->compensatePhysicalFiles($storedPaths);
                throw $e;
            }

            return $kaizen;
        });
    }

    /**
     * Remove physical files that were successfully stored during this operation
     * but whose outer DB transaction is about to be rolled back.
     */
    private function compensatePhysicalFiles(array $paths): void
    {
        if (empty($paths)) {
            return;
        }

        $disk = config('kaizen.attachments.disk', 'local');
        $storage = Storage::disk($disk);

        foreach ($paths as $path) {
            try {
                $storage->delete($path);
            } catch (\Exception $e) {
                Log::error('CreateKaizenDraftWithEvidence: Failed to compensate physical file on outer rollback.', [
                    'path_suffix' => basename($path),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
