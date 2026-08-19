<?php

namespace App\Actions\Kaizens;

use App\Enums\KaizenAttachmentContext;
use App\Models\Kaizen;
use App\Models\KaizenAttachment;
use App\Models\User;
use App\Services\Kaizens\KaizenAttachmentService;
use Illuminate\Support\Facades\DB;
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
        return DB::transaction(function () use ($updater, $kaizen, $validatedData) {
            // Load existing attachments for the Kaizen to ensure parent integrity
            // Use lockForUpdate to prevent race conditions during effective limit calculation
            $existingAttachments = $kaizen->attachments()->lockForUpdate()->get();

            $removeIds = $validatedData['remove_attachment_ids'] ?? [];
            $currentNewImages = $validatedData['current_situation_images'] ?? [];
            $proposedNewImages = $validatedData['proposed_situation_images'] ?? [];

            // Separate the valid attachments to remove (ensure they belong to this kaizen)
            $attachmentsToRemove = $existingAttachments->whereIn('id', $removeIds);

            if ($attachmentsToRemove->count() !== count($removeIds)) {
                throw ValidationException::withMessages([
                    'payload' => 'Geçersiz fotoğraf kaldırma isteği.',
                ]);
            }

            // Calculate effective count for CURRENT_SITUATION
            $currentExisting = $existingAttachments->where('context', KaizenAttachmentContext::CURRENT_SITUATION);
            $currentRemoved = $attachmentsToRemove->where('context', KaizenAttachmentContext::CURRENT_SITUATION);
            $effectiveCurrentCount = $currentExisting->count() - $currentRemoved->count() + count($currentNewImages);

            if ($effectiveCurrentCount > config('kaizen.attachments.max_per_context', 8)) {
                throw ValidationException::withMessages([
                    'current_situation_images' => 'Mevcut durum için en fazla '.config('kaizen.attachments.max_per_context', 8).' fotoğraf bulunabilir.',
                ]);
            }

            // Calculate effective count for PROPOSED_SITUATION
            $proposedExisting = $existingAttachments->where('context', KaizenAttachmentContext::PROPOSED_SITUATION);
            $proposedRemoved = $attachmentsToRemove->where('context', KaizenAttachmentContext::PROPOSED_SITUATION);
            $effectiveProposedCount = $proposedExisting->count() - $proposedRemoved->count() + count($proposedNewImages);

            if ($effectiveProposedCount > config('kaizen.attachments.max_per_context', 8)) {
                throw ValidationException::withMessages([
                    'proposed_situation_images' => 'Önerilen durum için en fazla '.config('kaizen.attachments.max_per_context', 8).' fotoğraf bulunabilir.',
                ]);
            }

            // 1. Delete metadata for removed attachments
            if ($attachmentsToRemove->isNotEmpty()) {
                KaizenAttachment::destroy($attachmentsToRemove->pluck('id'));
            }

            // 2. Add new current attachments
            if (! empty($currentNewImages)) {
                $this->attachmentService->storeMany(
                    $kaizen,
                    $updater,
                    KaizenAttachmentContext::CURRENT_SITUATION,
                    $currentNewImages
                );
            }

            // 3. Add new proposed attachments
            if (! empty($proposedNewImages)) {
                $this->attachmentService->storeMany(
                    $kaizen,
                    $updater,
                    KaizenAttachmentContext::PROPOSED_SITUATION,
                    $proposedNewImages
                );
            }

            // 4. Update core Kaizen scalar data
            $updatedKaizen = $this->updateKaizenDraft->execute($updater, $kaizen, $validatedData);

            // Register an after-commit callback to physically delete the removed files
            if ($attachmentsToRemove->isNotEmpty()) {
                DB::afterCommit(function () use ($attachmentsToRemove) {
                    $this->attachmentService->deletePhysicalFiles($attachmentsToRemove);
                });
            }

            return $updatedKaizen;
        });
    }
}
