<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KaizenBenefit extends Model
{
    use HasFactory;

    protected $fillable = [
        'kaizen_id',
        'benefit_type_id',
        'expected_value',
        'expected_note',
        'realized_value',
        'realized_note',
    ];

    protected function casts(): array
    {
        return [
            'expected_value' => 'decimal:4',
            'realized_value' => 'decimal:4',
        ];
    }

    public function kaizen(): BelongsTo
    {
        return $this->belongsTo(Kaizen::class);
    }

    public function benefitType(): BelongsTo
    {
        return $this->belongsTo(BenefitType::class);
    }
}
