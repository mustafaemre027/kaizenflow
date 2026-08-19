<?php

namespace App\Http\Controllers;

use App\Models\Kaizen;
use App\Models\KaizenAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class KaizenAttachmentController extends Controller
{
    public function show(Request $request, Kaizen $kaizen, KaizenAttachment $attachment)
    {
        // Parent integrity check (IDOR guard)
        if ($attachment->kaizen_id !== $kaizen->id) {
            abort(404);
        }

        // Authorization delegate to parent kaizen
        Gate::authorize('view', $kaizen);

        $disk = Storage::disk($attachment->storage_disk);

        if (! $disk->exists($attachment->storage_path)) {
            abort(404);
        }

        $allowedMimes = config('kaizen.attachments.allowed_mimes', ['image/jpeg', 'image/png', 'image/webp']);

        if (! in_array($attachment->mime_type, $allowedMimes, true)) {
            abort(404);
        }

        $headers = [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Disposition' => 'inline; filename="'.basename($attachment->storage_path).'"',
        ];

        return response()->streamDownload(function () use ($disk, $attachment) {
            $stream = $disk->readStream($attachment->storage_path);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, basename($attachment->storage_path), $headers);
    }

    public function download(Request $request, Kaizen $kaizen, KaizenAttachment $attachment)
    {
        if ($attachment->kaizen_id !== $kaizen->id) {
            abort(404);
        }

        Gate::authorize('view', $kaizen);

        $disk = Storage::disk($attachment->storage_disk);

        if (! $disk->exists($attachment->storage_path)) {
            abort(404);
        }

        $headers = [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ];

        $filename = $attachment->original_name ?? basename($attachment->storage_path);
        // Sanitize filename minimally to avoid header injection issues
        $filename = str_replace(['"', "\r", "\n"], '', $filename);

        return response()->streamDownload(function () use ($disk, $attachment) {
            $stream = $disk->readStream($attachment->storage_path);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $filename, $headers);
    }
}
