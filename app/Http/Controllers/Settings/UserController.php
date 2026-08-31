<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Users\CreateUserWithInvitation;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\Users\IndexUserRequest;
use App\Http\Requests\Settings\Users\StoreUserRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(IndexUserRequest $request): View
    {
        $query = User::with('department');

        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $search = '%'.$request->q.'%';
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        if ($request->filled('setup')) {
            $isPending = $request->setup === 'pending';
            $query->where('must_set_password', $isPending);
        }

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        $users = $query->orderBy($sort, $direction)->paginate(15)->withQueryString();

        $departments = Department::orderBy('name')->get();

        return view('settings.users.index', compact('users', 'departments'));
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $roles = UserRole::cases();

        return view('settings.users.create', compact('departments', 'roles'));
    }

    public function store(StoreUserRequest $request, CreateUserWithInvitation $action): RedirectResponse
    {
        $result = $action->execute($request->user(), $request->validated());

        if ($result['success']) {
            return redirect()->route('settings.users.index')->with('success', $result['message']);
        }

        return redirect()->route('settings.users.index')->with('warning', $result['message']);
    }
}
