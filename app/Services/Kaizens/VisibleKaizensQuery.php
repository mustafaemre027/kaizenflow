<?php

namespace App\Services\Kaizens;

use App\Enums\UserCapability;
use App\Models\Kaizen;
use App\Models\User;
use App\Services\UserCapabilityResolver;
use Illuminate\Database\Eloquent\Builder;

class VisibleKaizensQuery
{
    public function forUser(User $user): Builder
    {
        $query = Kaizen::query();

        if (! $user->is_active) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        /** @var UserCapabilityResolver $resolver */
        $resolver = app(UserCapabilityResolver::class);

        if ($resolver->allowsSystem($user, UserCapability::KAIZEN_OPEX_REVIEW)) {
            return $query;
        }

        $departmentGrants = $user->capabilityGrants()
            ->where('capability', UserCapability::KAIZEN_DEPARTMENT_APPROVE->value)
            ->where('is_active', true)
            ->pluck('department_id')
            ->filter()
            ->toArray();

        $query->where(function (Builder $q) use ($user, $departmentGrants) {
            $q->where('creator_user_id', $user->id)
                ->orWhere('assigned_user_id', $user->id);

            if (! empty($departmentGrants)) {
                $q->orWhereIn('department_id', $departmentGrants);
            }
        });

        return $query;
    }
}
