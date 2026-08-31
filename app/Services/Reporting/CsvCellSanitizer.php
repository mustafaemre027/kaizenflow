<?php

namespace App\Services\Reporting;

class CsvCellSanitizer
{
    /**
     * Sanitizes text to prevent CSV formula injection attacks in spreadsheet applications.
     */
    public function sanitizeText(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Check if the first meaningful character is a formula trigger (=, +, -, @)
        // Leading whitespace in front of these characters can also trigger formulas in Excel.
        if (preg_match('/^\s*[\=\+\-\@]/', $value)) {
            return "'".$value;
        }

        return $value;
    }
}
