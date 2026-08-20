<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalGroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_group_id',
        'user_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ApprovalGroup::class, 'approval_group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
