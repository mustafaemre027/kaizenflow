<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BenefitType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit_label',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function kaizenBenefits(): HasMany
    {
        return $this->hasMany(KaizenBenefit::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
