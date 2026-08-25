<?php

namespace App\Models;

use App\Enums\ApproverResolutionMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'version',
        'is_active',
        'is_default',
        'published_at',
        'approver_resolution_mode',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'published_at' => 'immutable_datetime',
        'approver_resolution_mode' => ApproverResolutionMode::class,
    ];

    public function stages(): HasMany
    {
        return $this->hasMany(ApprovalStage::class)->orderBy('sequence');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(KaizenWorkflowInstance::class);
    }
}
