<?php

namespace App\Http\View\Composers;

use App\Enums\UserCapability;
use App\Models\Category;
use App\Models\User;
use App\Services\UserCapabilityResolver;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class NavigationComposer
{
    protected UserCapabilityResolver $capabilityResolver;

    public function __construct(UserCapabilityResolver $capabilityResolver)
    {
        $this->capabilityResolver = $capabilityResolver;
    }

    public function compose(View $view)
    {
        $user = auth()->user();

        if (! $user) {
            $view->with('navContext', [
                'canViewDashboard' => false,
                'canViewApprovals' => false,
                'canViewHistory' => false,
                'canViewSettings' => false,
                'canViewUsers' => false,
            ]);

            return;
        }

        $isActive = $user->is_active;

        // Perform these checks once per request for the navbar
        $canViewDashboard = $isActive && $this->capabilityResolver->allowsSystem($user, UserCapability::ORGANIZATION_VIEW);

        $canViewApprovals = $isActive && $user->approvalGroupMemberships()
            ->where('is_active', true)
            ->whereHas('group', function ($query) {
                $query->where('is_active', true);
            })->exists();

        $canViewHistory = $isActive && $user->canAccessReviewedHistory();

        $canViewSettings = Gate::allows('viewAny', Category::class);
        $canViewUsers = Gate::allows('viewAny', User::class);

        $view->with('navContext', [
            'canViewDashboard' => $canViewDashboard,
            'canViewApprovals' => $canViewApprovals,
            'canViewHistory' => $canViewHistory,
            'canViewSettings' => $canViewSettings,
            'canViewUsers' => $canViewUsers,
        ]);
    }
}
