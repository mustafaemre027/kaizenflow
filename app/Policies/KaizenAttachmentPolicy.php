<?php

namespace App\Policies;

use App\Models\Kaizen;
use App\Models\KaizenAttachment;
use App\Models\User;

class KaizenAttachmentPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, KaizenAttachment $kaizenAttachment): bool
    {
        return $user->can('view', $kaizenAttachment->kaizen);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Kaizen $kaizen): bool
    {
        return $user->can('update', $kaizen);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, KaizenAttachment $kaizenAttachment): bool
    {
        return $user->can('update', $kaizenAttachment->kaizen);
    }
}
