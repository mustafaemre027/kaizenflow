<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Users\CreateUserWithInvitation;
use App\Actions\Users\SendUserInvitation;
use App\Actions\Users\SetUserStatus;
use App\Actions\Users\UpdateUser;
use App\Enums\UserRole;
use App\Exceptions\LastAuthorizationManagerException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\Users\IndexUserRequest;
use App\Http\Requests\Settings\Users\SetUserStatusRequest;
use App\Http\Requests\Settings\Users\StoreUserRequest;
use App\Http\Requests\Settings\Users\UpdateUserRequest;
use App\Models\Department;
use App\Models\User;
use DomainException;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
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

    public function edit(User $user): View
    {
        Gate::authorize('view', $user);

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $roles = UserRole::cases();

        return view('settings.users.edit', compact('user', 'departments', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUser $action): RedirectResponse
    {
        Gate::authorize('update', $user);

        try {
            $result = $action->execute($request->user(), $user, $request->validated());

            if ($result['success']) {
                $status = str_contains($result['message'] ?? '', 'gönderilemedi') ? 'warning' : 'success';

                return redirect()->route('settings.users.index')->with($status, $result['message']);
            }

            return redirect()->back()->with('error', 'Kullanıcı güncellenemedi.');
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'İşlem tamamlanamadı. Lütfen tekrar deneyin.')->withInput();
        }
    }

    public function setStatus(SetUserStatusRequest $request, User $user, SetUserStatus $action): RedirectResponse
    {
        Gate::authorize('setStatus', $user);

        try {
            $result = $action->execute($request->user(), $user, (bool) $request->validated('is_active'));

            return redirect()->back()->with('success', $result['message']);
        } catch (LastAuthorizationManagerException $e) {
            return redirect()->back()->with('error', 'Son yetkilendirme yöneticisi pasife alınamaz.');
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'İşlem tamamlanamadı. Lütfen tekrar deneyin.');
        }
    }

    public function resendInvitation(User $user, SendUserInvitation $action): RedirectResponse
    {
        Gate::authorize('resendInvitation', $user);

        try {
            $status = $action->execute(request()->user(), $user);

            if ($status === Password::RESET_LINK_SENT) {
                return redirect()->back()->with('success', 'Davet e-postası başarıyla gönderildi.');
            } elseif ($status === Password::RESET_THROTTLED) {
                return redirect()->back()->with('warning', 'Davet kısa süre önce gönderildi. Lütfen tekrar denemeden önce bekleyin.');
            }

            return redirect()->back()->with('warning', 'Davet gönderilemedi. Lütfen daha sonra tekrar deneyin.');
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'İşlem tamamlanamadı. Lütfen tekrar deneyin.');
        }
    }
}
