<?php

namespace App\Console\Commands;

use App\Services\Kaizens\KaizenAttachmentIntegrityService;
use Illuminate\Console\Command;

class AuditKaizenAttachments extends Command
{
    protected $signature = 'kaizen:attachments:audit
        {--verify-hashes : Compute SHA-256 for each physical file and compare to DB metadata}
        {--delete-orphans : Delete orphan physical files that have no DB record and are older than the grace period}';

    protected $description = 'Audit the integrity of KaizenAttachment storage. Read-only by default.';

    public function __construct(private readonly KaizenAttachmentIntegrityService $integrity)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $verifyHashes = (bool) $this->option('verify-hashes');
        $deleteOrphans = (bool) $this->option('delete-orphans');

        // ─── Safety gate ───────────────────────────────────────────────────────
        if (! $this->integrity->isManagedPrefixSafe()) {
            $this->error('ABORT: The configured managed_prefix is empty, root-level, or unsafe.');
            $this->line('Configure kaizen.attachments.managed_prefix to a safe sub-directory (e.g. "kaizens").');

            return self::FAILURE;
        }

        $this->info('Running KaizenAttachment storage integrity audit…');

        if ($verifyHashes) {
            $this->line('  → Hash verification enabled (--verify-hashes)');
        }

        if ($deleteOrphans) {
            $this->line('  → Orphan cleanup enabled (--delete-orphans)');
        }

        // ─── Run audit ─────────────────────────────────────────────────────────
        try {
            $result = $this->integrity->audit($verifyHashes);
        } catch (\Exception $e) {
            $this->error('Audit failed with an unexpected error: '.$e->getMessage());

            return self::FAILURE;
        }

        $summary = $result['summary'];
        $orphanResults = $result['orphanResults'];

        // ─── Print results ─────────────────────────────────────────────────────
        $this->newLine();
        $this->line("  Scanned DB attachments : {$summary['db_scanned']}");
        $this->line("  Scanned managed files  : {$summary['files_scanned']}");
        $this->newLine();
        $this->line("  ✓ OK              : {$summary[KaizenAttachmentIntegrityService::STATUS_OK]}");
        $this->line("  ✗ Missing files   : {$summary[KaizenAttachmentIntegrityService::STATUS_MISSING_FILE]}");
        $this->line("  ✗ Orphan files    : {$summary[KaizenAttachmentIntegrityService::STATUS_ORPHAN_FILE]}");
        $this->line("  ✗ Hash mismatches : {$summary[KaizenAttachmentIntegrityService::STATUS_HASH_MISMATCH]}");
        $this->line("  ✗ Size mismatches : {$summary[KaizenAttachmentIntegrityService::STATUS_SIZE_MISMATCH]}");
        $this->line("  ✗ Invalid MIME    : {$summary[KaizenAttachmentIntegrityService::STATUS_INVALID_MIME]}");
        $this->line("  ✗ Invalid disk    : {$summary[KaizenAttachmentIntegrityService::STATUS_INVALID_DISK]}");
        $this->line("  ✗ Unsafe paths    : {$summary[KaizenAttachmentIntegrityService::STATUS_UNSAFE_PATH]}");
        $this->newLine();

        // ─── Orphan cleanup ────────────────────────────────────────────────────
        if ($deleteOrphans && $orphanResults->isNotEmpty()) {
            $this->line('Running orphan cleanup…');
            $cleanup = $this->integrity->deleteOrphanFiles($orphanResults);
            $this->line("  Deleted : {$cleanup['deleted']}");
            $this->line("  Skipped : {$cleanup['skipped']} (too new / unsafe path)");
            $this->line("  Errors  : {$cleanup['errors']}");
            $this->newLine();
        }

        // ─── Exit code ─────────────────────────────────────────────────────────
        $hasAnomalies = $summary[KaizenAttachmentIntegrityService::STATUS_MISSING_FILE] > 0
            || $summary[KaizenAttachmentIntegrityService::STATUS_ORPHAN_FILE] > 0
            || $summary[KaizenAttachmentIntegrityService::STATUS_HASH_MISMATCH] > 0
            || $summary[KaizenAttachmentIntegrityService::STATUS_SIZE_MISMATCH] > 0
            || $summary[KaizenAttachmentIntegrityService::STATUS_INVALID_MIME] > 0
            || $summary[KaizenAttachmentIntegrityService::STATUS_INVALID_DISK] > 0
            || $summary[KaizenAttachmentIntegrityService::STATUS_UNSAFE_PATH] > 0;

        if ($hasAnomalies) {
            $this->warn('Integrity anomalies detected. Review the output above.');

            return self::FAILURE;
        }

        $this->info('All attachments passed integrity checks.');

        return self::SUCCESS;
    }
}
