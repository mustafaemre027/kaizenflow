<?php

namespace Tests\Unit\Services;

use App\Models\Kaizen;
use App\Services\KaizenCodeGenerator;
use Illuminate\Support\Carbon;
use LogicException;
use Tests\TestCase;

class KaizenCodeGeneratorTest extends TestCase
{
    private KaizenCodeGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new KaizenCodeGenerator;
    }

    public function test_it_generates_correct_format_for_id_1(): void
    {
        $kaizen = new Kaizen;
        $kaizen->id = 1;
        $kaizen->created_at = Carbon::create(2026, 8, 17);

        $code = $this->generator->generate($kaizen);

        $this->assertEquals('KZN-2026-000001', $code);
    }

    public function test_it_pads_id_42_correctly(): void
    {
        $kaizen = new Kaizen;
        $kaizen->id = 42;
        $kaizen->created_at = Carbon::create(2026, 8, 17);

        $code = $this->generator->generate($kaizen);

        $this->assertEquals('KZN-2026-000042', $code);
    }

    public function test_it_generates_correct_format_for_id_999999(): void
    {
        $kaizen = new Kaizen;
        $kaizen->id = 999999;
        $kaizen->created_at = Carbon::create(2027, 1, 1);

        $code = $this->generator->generate($kaizen);

        $this->assertEquals('KZN-2027-999999', $code);
    }

    public function test_it_does_not_truncate_id_exceeding_six_digits(): void
    {
        $kaizen = new Kaizen;
        $kaizen->id = 1000000;
        $kaizen->created_at = Carbon::create(2028, 5, 5);

        $code = $this->generator->generate($kaizen);

        $this->assertEquals('KZN-2028-1000000', $code);
    }

    public function test_it_generates_different_codes_for_different_ids(): void
    {
        $kaizen1 = new Kaizen;
        $kaizen1->id = 10;
        $kaizen1->created_at = Carbon::create(2026, 1, 1);

        $kaizen2 = new Kaizen;
        $kaizen2->id = 11;
        $kaizen2->created_at = Carbon::create(2026, 1, 1);

        $this->assertNotEquals($this->generator->generate($kaizen1), $this->generator->generate($kaizen2));
    }

    public function test_it_throws_exception_if_model_is_unsaved_without_id(): void
    {
        $kaizen = new Kaizen;
        $kaizen->created_at = Carbon::create(2026, 1, 1);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot generate code for an unsaved Kaizen model or a model without created_at timestamp.');

        $this->generator->generate($kaizen);
    }

    public function test_it_throws_exception_if_created_at_is_missing(): void
    {
        $kaizen = new Kaizen;
        $kaizen->id = 1;
        // created_at is null

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot generate code for an unsaved Kaizen model or a model without created_at timestamp.');

        $this->generator->generate($kaizen);
    }

    public function test_it_matches_regex_format(): void
    {
        $kaizen = new Kaizen;
        $kaizen->id = 123;
        $kaizen->created_at = Carbon::create(2026, 8, 17);

        $code = $this->generator->generate($kaizen);

        $this->assertMatchesRegularExpression('/^KZN-\d{4}-\d{6,}$/', $code);
    }
}
