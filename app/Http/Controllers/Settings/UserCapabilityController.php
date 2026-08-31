<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Authorization\GrantDepartmentCapability;
use App\Actions\Authorization\GrantSystemCapability;
use App\Actions\Authorization\RevokeDepartmentCapability;
use App\Actions\Authorization\RevokeSystemCapability;
use App\Enums\CapabilityScope;
use App\Enums\UserCapability;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\Users\SetDepartmentCapabilityRequest;
use App\Http\Requests\Settings\Users\SetSystemCapabilityRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserCapabilityController extends Controller
{
    public function show(Request $request, User $user): View
    {
        Gate::authorize('viewCapabilities', $user);

        $systemGrants = $user->systemCapabilityGrants()->get()->keyBy('capability');
        $departmentGrants = $user->capabilityGrants()->with('department')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        $capabilities = collect(UserCapability::cases());
        $systemCapabilities = $capabilities->filter(fn ($c) => $c->scope() === CapabilityScope::SYSTEM);
        $departmentCapabilities = $capabilities->filter(fn ($c) => $c->scope() === CapabilityScope::DEPARTMENT);

        return view('settings.users.capabilities', [
            'targetUser' => $user,
            'systemGrants' => $systemGrants,
            'departmentGrants' => $departmentGrants,
            'departments' => $departments,
            'systemCapabilities' => $systemCapabilities,
            'departmentCapabilities' => $departmentCapabilities,
        ]);
    }

    public function setSystem(
        SetSystemCapabilityRequest $request,
        User $user,
        GrantSystemCapability $grantAction,
        RevokeSystemCapability $revokeAction
    ): RedirectResponse {
        $capability = UserCapability::from($request->validated('capability'));
        $isActive = $request->validated('is_active');

        if ($isActive) {
            $grantAction->execute($request->user(), $user, $capability);
        } else {
            $revokeAction->execute($request->user(), $user, $capability);
        }

        return redirect()->back()->with('success', 'Sistem yetkisi güncellendi.');
    }

    public function setDepartment(
        SetDepartmentCapabilityRequest $request,
        User $user,
        GrantDepartmentCapability $grantAction,
        RevokeDepartmentCapability $revokeAction
    ): RedirectResponse {
        $capability = UserCapability::from($request->validated('capability'));
        $department = Department::findOrFail($request->validated('department_id'));
        $isActive = $request->validated('is_active');

        if ($isActive) {
            $grantAction->execute($request->user(), $user, $department, $capability);
        } else {
            $revokeAction->execute($request->user(), $user, $department, $capability);
        }

        return redirect()->back()->with('success', 'Departman yetkisi güncellendi.');
    }
}
