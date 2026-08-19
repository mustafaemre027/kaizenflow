<?php

namespace App\Models;

use App\Enums\KaizenAttachmentContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KaizenAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'kaizen_id',
        'uploaded_by_user_id',
        'context',
        'original_name',
        'storage_disk',
        'storage_path',
        'mime_type',
        'size_bytes',
        'sha256',
        'caption',
        'sort_order',
    ];

    protected $casts = [
        'context' => KaizenAttachmentContext::class,
        'size_bytes' => 'integer',
        'sort_order' => 'integer',
    ];

    public function kaizen(): BelongsTo
    {
        return $this->belongsTo(Kaizen::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
