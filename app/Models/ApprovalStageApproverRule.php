<?php

namespace App\Models;

use App\Enums\ApprovalApproverScopeSource;
use App\Enums\UserCapability;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStageApproverRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_stage_id',
        'capability',
        'scope_source',
        'is_active',
    ];

    protected $casts = [
        'capability' => UserCapability::class,
        'scope_source' => ApprovalApproverScopeSource::class,
        'is_active' => 'boolean',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ApprovalStage::class, 'approval_stage_id');
    }
}
