<?php

namespace App\Services\Kaizens;

use App\Enums\UserRole;
use App\Models\Kaizen;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class VisibleKaizensQuery
{
    public function forUser(User $user): Builder
    {
        $query = Kaizen::query();

        if ($user->role === UserRole::OPEX_SPECIALIST || $user->role === UserRole::ADMIN) {
            return $query;
        }

        $query->where(function (Builder $q) use ($user) {
            $q->where('creator_user_id', $user->id)
                ->orWhere('assigned_user_id', $user->id);

            if ($user->role === UserRole::MANAGER && $user->department_id) {
                $q->orWhere('department_id', $user->department_id);
            }
        });

        return $query;
    }
}
