<?php

namespace App\Actions\Kaizens;

use App\Enums\KaizenAttachmentContext;
use App\Models\Kaizen;
use App\Models\KaizenAttachment;
use App\Models\User;
use App\Services\Kaizens\KaizenAttachmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UpdateKaizenDraftWithEvidence
{
    public function __construct(
        private readonly UpdateKaizenDraft $updateKaizenDraft,
        private readonly KaizenAttachmentService $attachmentService
    ) {}

    public function execute(
        User $updater,
        Kaizen $kaizen,
        array $validatedData
    ): Kaizen {
        $maxPerContext = (int) config('kaizen.attachments.max_images_per_context', 8);

        return DB::transaction(function () use ($updater, $kaizen, $validatedData, $maxPerContext) {
            // Load existing attachments for the Kaizen to ensure parent integrity.
            // Lock for update to prevent race conditions during effective limit calculation.
            $existingAttachments = $kaizen->attachments()->lockForUpdate()->get();

            $removeIds = $validatedData['remove_attachment_ids'] ?? [];
            $currentNewImages = $validatedData['current_situation_images'] ?? [];
            $proposedNewImages = $validatedData['proposed_situation_images'] ?? [];

            // Validate removal set belongs to this Kaizen
            $attachmentsToRemove = $existingAttachments->whereIn('id', $removeIds);

            if ($attachmentsToRemove->count() !== count($removeIds)) {
                throw ValidationException::withMessages([
                    'payload' => 'Geçersiz fotoğraf kaldırma isteği.',
                ]);
            }

            // --- Effective count: CURRENT_SITUATION ---
            $currentExisting = $existingAttachments->where('context', KaizenAttachmentContext::CURRENT_SITUATION);
            $currentRemoved = $attachmentsToRemove->where('context', KaizenAttachmentContext::CURRENT_SITUATION);
            $effectiveCurrentCount = $currentExisting->count() - $currentRemoved->count() + count($currentNewImages);

            if ($effectiveCurrentCount > $maxPerContext) {
                throw ValidationException::withMessages([
                    'current_situation_images' => "Mevcut durum için en fazla {$maxPerContext} fotoğraf bulunabilir.",
                ]);
            }

            // --- Effective count: PROPOSED_SITUATION ---
            $proposedExisting = $existingAttachments->where('context', KaizenAttachmentContext::PROPOSED_SITUATION);
            $proposedRemoved = $attachmentsToRemove->where('context', KaizenAttachmentContext::PROPOSED_SITUATION);
            $effectiveProposedCount = $proposedExisting->count() - $proposedRemoved->count() + count($proposedNewImages);

            if ($effectiveProposedCount > $maxPerContext) {
                throw ValidationException::withMessages([
                    'proposed_situation_images' => "Önerilen durum için en fazla {$maxPerContext} fotoğraf bulunabilir.",
                ]);
            }

            // Track every physical file written in this operation so we can
            // compensate if a later step in the outer transaction fails.
            $storedPaths = [];

            try {
                // 1. Delete metadata for removed attachments (within the outer transaction)
                if ($attachmentsToRemove->isNotEmpty()) {
                    KaizenAttachment::destroy($attachmentsToRemove->pluck('id'));
                }

                // 2. Add new current attachments
                if (! empty($currentNewImages)) {
                    $stored = $this->attachmentService->storeMany(
                        $kaizen,
                        $updater,
                        KaizenAttachmentContext::CURRENT_SITUATION,
                        $currentNewImages
                    );
                    $storedPaths = array_merge($storedPaths, $stored->pluck('storage_path')->all());
                }

                // 3. Add new proposed attachments
                if (! empty($proposedNewImages)) {
                    $stored = $this->attachmentService->storeMany(
                        $kaizen,
                        $updater,
                        KaizenAttachmentContext::PROPOSED_SITUATION,
                        $proposedNewImages
                    );
                    $storedPaths = array_merge($storedPaths, $stored->pluck('storage_path')->all());
                }

                // 4. Update core Kaizen scalar data
                $updatedKaizen = $this->updateKaizenDraft->execute($updater, $kaizen, $validatedData);

            } catch (\Throwable $e) {
                // Outer transaction will be rolled back. Clean up any physical files
                // written in steps 2–3 that the inner storeMany did not clean up.
                $this->compensatePhysicalFiles($storedPaths);
                throw $e;
            }

            // Register an after-commit callback to physically delete the removed files.
            // If this fails the DB metadata is already gone; the orphan will be caught
            // by the integrity audit.
            if ($attachmentsToRemove->isNotEmpty()) {
                DB::afterCommit(function () use ($attachmentsToRemove) {
                    $this->attachmentService->deletePhysicalFiles($attachmentsToRemove);
                });
            }

            return $updatedKaizen;
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
            } catch (\Throwable $e) {
                Log::error('UpdateKaizenDraftWithEvidence: Failed to compensate physical file on outer rollback.', [
                    'path_suffix' => basename($path),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
