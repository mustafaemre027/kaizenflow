<?php

namespace App\Http\Controllers;

use App\Enums\KaizenPriority;
use App\Enums\KaizenStatus;
use App\Enums\UserCapability;
use App\Http\Requests\ExportKaizenReportRequest;
use App\Queries\KaizenCsvExportQuery;
use App\Services\Reporting\CsvCellSanitizer;
use App\Services\UserCapabilityResolver;
use Carbon\Carbon;

class KaizenReportExportController extends Controller
{
    public function __construct(
        private readonly UserCapabilityResolver $capabilityResolver,
        private readonly KaizenCsvExportQuery $exportQuery,
        private readonly CsvCellSanitizer $sanitizer
    ) {}

    public function export(ExportKaizenReportRequest $request)
    {
        $user = $request->user();

        // 1. Authorization matching Dashboard
        if (! $this->capabilityResolver->allowsSystem($user, UserCapability::ORGANIZATION_VIEW)) {
            abort(403, 'Rapor dışa aktarma yetkiniz bulunmamaktadır.');
        }

        $filters = $request->validated();
        $filename = 'kaizen-raporu-'.now()->format('Ymd-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ];

        return response()->streamDownload(function () use ($user, $filters) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Turkish Excel support
            fwrite($file, "\xEF\xBB\xBF");

            // Explicit CSV Header
            fputcsv($file, [
                'Kaizen Kodu',
                'Başlık',
                'Durum',
                'Departman',
                'Kategori',
                'Öncelik',
                'Hedef Tarihi',
                'Oluşturulma Tarihi',
                'Fayda Türü',
                'Beklenen Değer',
                'Gerçekleşen Değer',
                'Birim',
            ]);

            // Execute cursor query
            $cursor = $this->exportQuery->execute($user, $filters);

            foreach ($cursor as $row) {
                // Map enums (models hydrate attributes to enums)
                $statusLabel = $row->status instanceof KaizenStatus ? $row->status->label() : (KaizenStatus::tryFrom($row->status)?->label() ?? $row->status);
                $priorityLabel = '';
                if ($row->priority) {
                    $priorityLabel = $row->priority instanceof KaizenPriority ? $row->priority->label() : (KaizenPriority::tryFrom($row->priority)?->label() ?? $row->priority);
                }

                // Format dates safely
                $targetDate = $row->target_date ? Carbon::parse($row->target_date)->format('Y-m-d') : '';
                $createdAt = $row->created_at ? Carbon::parse($row->created_at)->format('Y-m-d H:i:s') : '';

                // Map output
                fputcsv($file, [
                    $this->sanitizer->sanitizeText($row->code),
                    $this->sanitizer->sanitizeText($row->title),
                    $this->sanitizer->sanitizeText($statusLabel),
                    $this->sanitizer->sanitizeText($row->department_name ?? 'Departmansız'),
                    $this->sanitizer->sanitizeText($row->category_name ?? 'Kategorisiz'),
                    $this->sanitizer->sanitizeText($priorityLabel),
                    $targetDate,
                    $createdAt,
                    $this->sanitizer->sanitizeText($row->benefit_type_name ?? ''),
                    $row->expected_value ?? '', // Numeric, DO NOT sanitize
                    $row->realized_value ?? '', // Numeric, DO NOT sanitize
                    $this->sanitizer->sanitizeText($row->benefit_unit_label ?? ''),
                ]);
            }

            fclose($file);
        }, $filename, $headers);
    }
}
