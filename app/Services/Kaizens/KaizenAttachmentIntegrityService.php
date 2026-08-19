<?php

namespace App\Services\Kaizens;

use App\Models\KaizenAttachment;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Audits the physical integrity of KaizenAttachment storage vs DB records.
 *
 * All operations are READ-ONLY by default.
 * Destructive operations only occur when explicitly requested via flags.
 */
class KaizenAttachmentIntegrityService
{
    // Audit result status constants
    public const STATUS_OK = 'OK';

    public const STATUS_MISSING_FILE = 'MISSING_FILE';

    public const STATUS_ORPHAN_FILE = 'ORPHAN_FILE';

    public const STATUS_HASH_MISMATCH = 'HASH_MISMATCH';

    public const STATUS_SIZE_MISMATCH = 'SIZE_MISMATCH';

    public const STATUS_INVALID_MIME = 'INVALID_MIME';

    public const STATUS_INVALID_DISK = 'INVALID_DISK';

    public const STATUS_UNSAFE_PATH = 'UNSAFE_PATH';

    public const STATUS_ORPHAN_TOO_NEW = 'ORPHAN_TOO_NEW';

    public const STATUS_ORPHAN_AGE_UNKNOWN = 'ORPHAN_AGE_UNKNOWN';

    /**
     * Run a full audit. Returns a structured summary array.
     *
     * @return array{db_results: Collection, orphan_results: Collection, summary: array}
     */
    public function audit(bool $verifyHashes = false): array
    {
        $dbResults = $this->auditDbAttachments($verifyHashes);
        $orphanResults = $this->auditOrphanFiles();

        // files_scanned = all physical files within managed prefix (DB-backed + orphans)
        $dbBackedFileCount = $dbResults->where('status', '!=', self::STATUS_MISSING_FILE)
            ->where('status', '!=', self::STATUS_INVALID_DISK)
            ->where('status', '!=', self::STATUS_UNSAFE_PATH)
            ->count();

        $summary = [
            'db_scanned' => $dbResults->count(),
            'files_scanned' => $dbBackedFileCount + $orphanResults->count(),
            self::STATUS_OK => $dbResults->where('status', self::STATUS_OK)->count(),
            self::STATUS_MISSING_FILE => $dbResults->where('status', self::STATUS_MISSING_FILE)->count(),
            self::STATUS_HASH_MISMATCH => $dbResults->where('status', self::STATUS_HASH_MISMATCH)->count(),
            self::STATUS_SIZE_MISMATCH => $dbResults->where('status', self::STATUS_SIZE_MISMATCH)->count(),
            self::STATUS_INVALID_MIME => $dbResults->where('status', self::STATUS_INVALID_MIME)->count(),
            self::STATUS_INVALID_DISK => $dbResults->where('status', self::STATUS_INVALID_DISK)->count(),
            self::STATUS_UNSAFE_PATH => $dbResults->where('status', self::STATUS_UNSAFE_PATH)->count(),
            self::STATUS_ORPHAN_FILE => $orphanResults->where('status', self::STATUS_ORPHAN_FILE)->count(),
            self::STATUS_ORPHAN_TOO_NEW => $orphanResults->where('status', self::STATUS_ORPHAN_TOO_NEW)->count(),
            self::STATUS_ORPHAN_AGE_UNKNOWN => $orphanResults->where('status', self::STATUS_ORPHAN_AGE_UNKNOWN)->count(),
        ];

        return compact('dbResults', 'orphanResults', 'summary');
    }

    /**
     * Audit each DB attachment record against physical storage.
     *
     * @return Collection<int, array{attachment_id: int, kaizen_id: int, status: string, detail: string|null}>
     */
    public function auditDbAttachments(bool $verifyHashes = false): Collection
    {
        $results = collect();
        $allowedMimes = config('kaizen.attachments.allowed_mimes', []);
        $allowedDisks = config('kaizen.attachments.allowed_disks', [config('kaizen.attachments.disk', 'local')]);
        $managedPrefix = $this->getManagedPrefix();

        KaizenAttachment::query()
            ->select(['id', 'kaizen_id', 'storage_disk', 'storage_path', 'mime_type', 'size_bytes', 'sha256'])
            ->chunk(100, function (Collection $attachments) use (
                &$results, $allowedMimes, $allowedDisks, $managedPrefix, $verifyHashes
            ) {
                foreach ($attachments as $attachment) {
                    $result = $this->inspectDbAttachment(
                        $attachment,
                        $allowedMimes,
                        $allowedDisks,
                        $managedPrefix,
                        $verifyHashes
                    );
                    $results->push($result);
                }
            });

        return $results;
    }

    /**
     * Check a single DB attachment for integrity issues.
     */
    protected function inspectDbAttachment(
        KaizenAttachment $attachment,
        array $allowedMimes,
        array $allowedDisks,
        string $managedPrefix,
        bool $verifyHashes
    ): array {
        $base = [
            'attachment_id' => $attachment->id,
            'kaizen_id' => $attachment->kaizen_id,
            'status' => self::STATUS_OK,
            'detail' => null,
        ];

        // 1. Disk allowlist check
        if (! in_array($attachment->storage_disk, $allowedDisks, true)) {
            return array_merge($base, [
                'status' => self::STATUS_INVALID_DISK,
                'detail' => "Disk '{$attachment->storage_disk}' is not in the allowed disks list.",
            ]);
        }

        // 2. Managed prefix boundary check
        if (! $this->isPathWithinManagedBoundary($attachment->storage_path, $managedPrefix)) {
            return array_merge($base, [
                'status' => self::STATUS_UNSAFE_PATH,
                'detail' => 'storage_path is outside the managed prefix boundary.',
            ]);
        }

        // 3. MIME allowlist check
        if (! in_array($attachment->mime_type, $allowedMimes, true)) {
            return array_merge($base, [
                'status' => self::STATUS_INVALID_MIME,
                'detail' => "mime_type '{$attachment->mime_type}' is not in the allowed mimes list.",
            ]);
        }

        // 4. Physical file existence
        $disk = Storage::disk($attachment->storage_disk);
        if (! $disk->exists($attachment->storage_path)) {
            return array_merge($base, [
                'status' => self::STATUS_MISSING_FILE,
                'detail' => 'Physical file does not exist on disk.',
            ]);
        }

        // 5. Size check
        $physicalSize = $disk->size($attachment->storage_path);
        if ($physicalSize !== $attachment->size_bytes) {
            return array_merge($base, [
                'status' => self::STATUS_SIZE_MISMATCH,
                'detail' => "DB size_bytes={$attachment->size_bytes} vs physical={$physicalSize}.",
            ]);
        }

        // 6. Hash verification (optional deep check)
        if ($verifyHashes && $attachment->sha256) {
            $physicalHash = $this->hashFileStream($disk, $attachment->storage_path);

            if ($physicalHash !== $attachment->sha256) {
                return array_merge($base, [
                    'status' => self::STATUS_HASH_MISMATCH,
                    'detail' => 'SHA-256 hash does not match DB record.',
                ]);
            }
        }

        return $base;
    }

    /**
     * Scan the managed prefix directory for files that have no DB record.
     *
     * @return Collection<int, array{path: string, status: string, detail: string|null}>
     */
    public function auditOrphanFiles(): Collection
    {
        $managedPrefix = $this->getManagedPrefix();
        $disk = config('kaizen.attachments.disk', 'local');
        $storage = Storage::disk($disk);
        $gracePeriodMinutes = (int) config('kaizen.attachments.orphan_grace_minutes', 10);
        $results = collect();

        // Load all known DB paths into a Set for O(1) lookup (avoiding N+1)
        $knownPaths = KaizenAttachment::query()->pluck('storage_path')->flip();

        try {
            $physicalFiles = $storage->allFiles($managedPrefix);
        } catch (\Exception $e) {
            Log::error('KaizenAttachmentIntegrityService: Storage listing failed — audit cannot continue.', [
                'error' => $e->getMessage(),
            ]);

            // Propagate so the command can report FAILURE rather than silently
            // appearing healthy with 0 orphans.
            throw $e;
        }

        foreach ($physicalFiles as $filePath) {
            if ($knownPaths->has($filePath)) {
                // File has a DB record — not an orphan
                continue;
            }

            // Determine if the orphan is too new to delete
            try {
                $lastModified = $storage->lastModified($filePath);
                $ageMinutes = (now()->timestamp - $lastModified) / 60;
                $isTooNew = $ageMinutes < $gracePeriodMinutes;

                $status = $isTooNew ? self::STATUS_ORPHAN_TOO_NEW : self::STATUS_ORPHAN_FILE;
                $detail = $isTooNew
                    ? "File is within the {$gracePeriodMinutes}-minute grace period (age ~{$ageMinutes} min)."
                    : 'Physical file exists but has no corresponding DB record.';
            } catch (\Throwable $e) {
                $ageMinutes = null;
                $status = self::STATUS_ORPHAN_AGE_UNKNOWN;
                $detail = 'Could not determine file age. Fail-closed to prevent accidental deletion.';
            }

            $results->push([
                'path' => $filePath,
                'status' => $status,
                'detail' => $detail,
                'age_minutes' => $ageMinutes,
            ]);
        }

        return $results;
    }

    /**
     * Delete orphan files that are beyond the grace period.
     * ONLY call this when the user has explicitly requested cleanup.
     *
     * @return array{deleted: int, skipped: int, errors: int}
     */
    public function deleteOrphanFiles(Collection $orphanResults): array
    {
        $managedPrefix = $this->getManagedPrefix();
        $disk = config('kaizen.attachments.disk', 'local');
        $storage = Storage::disk($disk);
        $deleted = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($orphanResults as $orphan) {
            // Only delete confirmed orphans (not too-new ones)
            if ($orphan['status'] !== self::STATUS_ORPHAN_FILE) {
                $skipped++;

                continue;
            }

            $path = $orphan['path'];

            // Final boundary check before any deletion
            if (! $this->isPathWithinManagedBoundary($path, $managedPrefix)) {
                $skipped++;
                Log::warning('KaizenAttachmentIntegrityService: Skipped deletion of unsafe path.', compact('path'));

                continue;
            }

            // TOCTOU guard: re-confirm the file has no DB record immediately before deletion.
            // A DB row may have been created after the initial audit snapshot.
            if (KaizenAttachment::where('storage_path', $path)->exists()) {
                $skipped++;
                Log::info('KaizenAttachmentIntegrityService: Skipped orphan deletion — DB record appeared since audit.', compact('path'));

                continue;
            }

            try {
                $success = $storage->delete($path);
                if ($success) {
                    $deleted++;
                    Log::info('KaizenAttachmentIntegrityService: Deleted orphan file.', compact('path'));
                } else {
                    $errors++;
                    Log::error('KaizenAttachmentIntegrityService: Failed to delete orphan (delete returned false).', compact('path'));
                }
            } catch (\Throwable $e) {
                $errors++;
                Log::error('KaizenAttachmentIntegrityService: Failed to delete orphan.', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return compact('deleted', 'skipped', 'errors');
    }

    /**
     * Validate the managed prefix is safe to use for cleanup operations.
     * Returns true if safe, false if unsafe (empty, root, etc.).
     */
    public function isManagedPrefixSafe(): bool
    {
        $prefix = $this->getManagedPrefix();

        if ($prefix === '' || $prefix === '/' || $prefix === '\\') {
            return false;
        }

        // Must be at least 2 characters and not look like a root path
        if (strlen($prefix) < 2) {
            return false;
        }

        // Reject absolute paths or path traversal sequences
        if (str_starts_with($prefix, '/') || str_starts_with($prefix, '\\') || str_contains($prefix, '..')) {
            return false;
        }

        return true;
    }

    /**
     * Returns whether a storage path is within the managed boundary.
     */
    public function isPathWithinManagedBoundary(string $path, string $managedPrefix): bool
    {
        if (empty($path) || empty($managedPrefix)) {
            return false;
        }

        // Null byte check
        if (str_contains($path, "\0") || str_contains($managedPrefix, "\0")) {
            return false;
        }

        // Normalize separators
        $normalizedPath = str_replace('\\', '/', $path);
        $normalizedPrefix = rtrim(str_replace('\\', '/', $managedPrefix), '/');

        // Reject absolute paths
        if (str_starts_with($normalizedPath, '/') || preg_match('/^[a-zA-Z]:\//', $normalizedPath)) {
            return false;
        }

        // Reject traversal segments
        $segments = explode('/', $normalizedPath);
        if (in_array('..', $segments, true)) {
            return false;
        }

        // The path must be strictly UNDER the managed prefix, not the prefix itself.
        if ($normalizedPath === $normalizedPrefix) {
            return false;
        }

        return str_starts_with($normalizedPath, $normalizedPrefix.'/');
    }

    /**
     * Compute SHA-256 of a stored file using stream reading.
     * Does NOT load the entire file into memory.
     */
    protected function hashFileStream(Filesystem $disk, string $path): string
    {
        $context = hash_init('sha256');
        $stream = $disk->readStream($path);

        if (! is_resource($stream)) {
            return '';
        }

        try {
            while (! feof($stream)) {
                $chunk = fread($stream, 65536); // 64 KB chunks
                if ($chunk !== false && $chunk !== '') {
                    hash_update($context, $chunk);
                }
            }
        } finally {
            fclose($stream);
        }

        return hash_final($context);
    }

    /**
     * Get the managed prefix from config.
     */
    protected function getManagedPrefix(): string
    {
        return (string) config('kaizen.attachments.managed_prefix', 'kaizens');
    }
}
