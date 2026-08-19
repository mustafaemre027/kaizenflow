<?php

namespace App\Services\Kaizens;

use App\Enums\KaizenAttachmentContext;
use App\Models\Kaizen;
use App\Models\KaizenAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
     * @throws \Exception
     */
    public function storeMany(Kaizen $kaizen, User $uploader, KaizenAttachmentContext $context, array $files): Collection
    {
        $disk = config('kaizen.attachments.disk', 'local');
        $storedPaths = [];
        $attachments = collect();

        $currentMaxSort = $kaizen->attachments()->where('context', $context)->max('sort_order') ?? 0;

        try {
            DB::beginTransaction();

            foreach ($files as $index => $file) {
                // Generate secure ULID-like or UUID name
                $filename = (string) Str::ulid().'.'.$file->extension();
                $path = "kaizens/{$kaizen->id}/evidence/{$context->value}/{$filename}";

                // Store file physically
                $storedPath = $file->storeAs("kaizens/{$kaizen->id}/evidence/{$context->value}", $filename, $disk);
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

        } catch (\Exception $e) {
            DB::rollBack();

            // Compensating cleanup of physical files
            foreach ($storedPaths as $path) {
                Storage::disk($disk)->delete($path);
            }

            throw $e;
        }
    }
}
