<?php

namespace Tests\Unit\Services\Reporting;

use App\Services\Reporting\CsvCellSanitizer;
use PHPUnit\Framework\TestCase;

class CsvCellSanitizerTest extends TestCase
{
    private CsvCellSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new CsvCellSanitizer;
    }

    public function test_sanitizes_formula_prefixes()
    {
        $this->assertEquals("'=SUM(A1:A2)", $this->sanitizer->sanitizeText('=SUM(A1:A2)'));
        $this->assertEquals("'+cmd", $this->sanitizer->sanitizeText('+cmd'));
        $this->assertEquals("'-2+3", $this->sanitizer->sanitizeText('-2+3'));
        $this->assertEquals("'@attack", $this->sanitizer->sanitizeText('@attack'));
    }

    public function test_sanitizes_with_leading_whitespace()
    {
        $this->assertEquals("' =SUM(A1:A2)", $this->sanitizer->sanitizeText(' =SUM(A1:A2)'));
        $this->assertEquals("'\t=SUM(A1:A2)", $this->sanitizer->sanitizeText("\t=SUM(A1:A2)"));
    }

    public function test_allows_normal_text()
    {
        $this->assertEquals('Normal text', $this->sanitizer->sanitizeText('Normal text'));
        $this->assertEquals('123.4500', $this->sanitizer->sanitizeText('123.4500'));
        $this->assertEquals('Kaizen Code: KZN-001', $this->sanitizer->sanitizeText('Kaizen Code: KZN-001'));
    }

    public function test_handles_null_and_empty()
    {
        $this->assertEquals('', $this->sanitizer->sanitizeText(null));
        $this->assertEquals('', $this->sanitizer->sanitizeText(''));
    }
}
