<?php

namespace App\Services\Kaizens;

use App\Enums\KaizenAttachmentContext;
use App\Models\Kaizen;
use App\Models\KaizenAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KaizenAttachmentService
{
    /**
     * Store multiple attachments for a context.
     *
     * @param  UploadedFile[]  $files
     * @return Collection<int, KaizenAttachment>
     *
     * @throws \Throwable
     */
    public function storeMany(Kaizen $kaizen, User $uploader, KaizenAttachmentContext $context, array $files): Collection
    {
        $integrityService = app(KaizenAttachmentIntegrityService::class);
        if (! $integrityService->isManagedPrefixSafe()) {
            throw new \LogicException('Configured attachment managed prefix is unsafe.');
        }

        $prefix = config('kaizen.attachments.managed_prefix');
        $disk = config('kaizen.attachments.disk', 'local');
        $storedPaths = [];
        $attachments = collect();

        $currentMaxSort = $kaizen->attachments()->where('context', $context)->max('sort_order') ?? 0;

        try {
            DB::beginTransaction();

            foreach ($files as $index => $file) {
                // Generate secure ULID-like or UUID name
                $filename = (string) Str::ulid().'.'.$file->extension();
                $directory = trim($prefix, '/')."/{$kaizen->id}/evidence/{$context->value}";

                // Store file physically
                $storedPath = $file->storeAs($directory, $filename, $disk);
                if (! $storedPath) {
                    throw new \Exception('Failed to store file physically.');
                }

                $storedPaths[] = $storedPath;

                // Create DB Record
                $attachments->push(KaizenAttachment::create([
                    'kaizen_id' => $kaizen->id,
                    'uploaded_by_user_id' => $uploader->id,
                    'context' => $context,
                    'original_name' => $file->getClientOriginalName(),
                    'storage_disk' => $disk,
                    'storage_path' => $storedPath,
                    'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(),
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                    'sort_order' => $currentMaxSort + $index + 1,
                ]));
            }

            DB::commit();

            return $attachments;

        } catch (\Throwable $e) {
            DB::rollBack();

            // Compensating cleanup of physical files
            foreach ($storedPaths as $path) {
                try {
                    Storage::disk($disk)->delete($path);
                } catch (\Throwable $cleanupException) {
                    Log::error('KaizenAttachmentService: Failed to compensate physical file on storeMany failure.', [
                        'path' => $path,
                        'error' => $cleanupException->getMessage(),
                    ]);
                }
            }

            throw $e;
        }
    }

    /**
     * Safely delete physical files for the given attachments.
     * This should typically be called AFTER a successful DB transaction
     * that has removed their metadata to ensure safe failure mode.
     *
     * Each file is only deleted if:
     *  1. Its storage_disk is in the allowed disks list.
     *  2. Its storage_path is within the managed path boundary.
     *
     * @param  Collection<int, KaizenAttachment>  $removedAttachments
     */
    public function deletePhysicalFiles(Collection $removedAttachments): void
    {
        $allowedDisks = config('kaizen.attachments.allowed_disks', [config('kaizen.attachments.disk', 'local')]);
        $managedPrefix = (string) config('kaizen.attachments.managed_prefix', 'kaizens');

        foreach ($removedAttachments as $attachment) {
            // Guard: disk allowlist
            if (! in_array($attachment->storage_disk, $allowedDisks, true)) {
                Log::warning('KaizenAttachmentService: Skipped physical delete — disk not in allowlist.', [
                    'attachment_id' => $attachment->id,
                    'kaizen_id' => $attachment->kaizen_id,
                    'disk' => $attachment->storage_disk,
                ]);

                continue;
            }

            // Guard: managed path boundary
            $integrityService = app(KaizenAttachmentIntegrityService::class);
            $withinBoundary = $integrityService->isPathWithinManagedBoundary($attachment->storage_path, $managedPrefix);

            if (! $withinBoundary) {
                Log::warning('KaizenAttachmentService: Skipped physical delete — path outside managed boundary.', [
                    'attachment_id' => $attachment->id,
                    'kaizen_id' => $attachment->kaizen_id,
                ]);

                continue;
            }

            try {
                $success = Storage::disk($attachment->storage_disk)->delete($attachment->storage_path);
                if (! $success) {
                    Log::error('KaizenAttachmentService: Failed to delete physical file (delete returned false).', [
                        'attachment_id' => $attachment->id,
                        'kaizen_id' => $attachment->kaizen_id,
                    ]);
                }
            } catch (\Throwable $e) {
                // Log the failure but do not interrupt the process, as the DB transaction is already committed.
                // An orphan file will remain, which can be cleaned up by the integrity audit command.
                Log::error('KaizenAttachmentService: Failed to delete physical file.', [
                    'attachment_id' => $attachment->id,
                    'kaizen_id' => $attachment->kaizen_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
