<?php

namespace App\Http\Controllers;

use App\Models\Kaizen;
use App\Models\KaizenAttachment;
use App\Services\Kaizens\KaizenAttachmentIntegrityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class KaizenAttachmentController extends Controller
{
    public function __construct(private readonly KaizenAttachmentIntegrityService $integrity) {}

    public function show(Request $request, Kaizen $kaizen, KaizenAttachment $attachment)
    {
        // Parent integrity check (IDOR guard)
        if ($attachment->kaizen_id !== $kaizen->id) {
            abort(404);
        }

        // Authorization delegate to parent kaizen
        Gate::authorize('view', $kaizen);

        $this->failClosedGuards($attachment);

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

        $stream = $disk->readStream($attachment->storage_path);

        if (! is_resource($stream)) {
            abort(404);
        }

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, $headers);
    }

    public function download(Request $request, Kaizen $kaizen, KaizenAttachment $attachment)
    {
        if ($attachment->kaizen_id !== $kaizen->id) {
            abort(404);
        }

        Gate::authorize('view', $kaizen);

        $this->failClosedGuards($attachment);

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

        $stream = $disk->readStream($attachment->storage_path);

        if (! is_resource($stream)) {
            abort(404);
        }

        return response()->streamDownload(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $filename, $headers);
    }

    /**
     * Fail-closed guards: disk allowlist + managed path boundary.
     * Aborts with 404 for any anomalous metadata to prevent arbitrary reads.
     */
    private function failClosedGuards(KaizenAttachment $attachment): void
    {
        $allowedDisks = config('kaizen.attachments.allowed_disks', [config('kaizen.attachments.disk', 'local')]);
        if (! in_array($attachment->storage_disk, $allowedDisks, true)) {
            abort(404);
        }

        $managedPrefix = (string) config('kaizen.attachments.managed_prefix', 'kaizens');
        if (! $this->integrity->isPathWithinManagedBoundary($attachment->storage_path, $managedPrefix)) {
            abort(404);
        }
    }
}
