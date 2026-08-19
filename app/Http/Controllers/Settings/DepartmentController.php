<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreDepartmentRequest;
use App\Http\Requests\Settings\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DepartmentController extends Controller
{
    public function store(StoreDepartmentRequest $request)
    {
        $validated = $request->validated();

        Department::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', 'Departman başarıyla eklendi.');
    }

    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $validated = $request->validated();

        $department->update($validated);

        return back()->with('success', 'Departman başarıyla güncellendi.');
    }

    public function toggleStatus(Request $request, Department $department)
    {
        Gate::authorize('update', $department);

        // Blocking deactivation if active users exist
        if ($department->is_active) {
            $activeUserCount = $department->users()->where('is_active', true)->count();
            if ($activeUserCount > 0) {
                return back()->with('error', "Bu departmanda {$activeUserCount} aktif kullanıcı bulunduğu için pasif yapılamaz.");
            }
        }

        $department->is_active = ! $department->is_active;
        $department->save();

        $status = $department->is_active ? 'aktif' : 'pasif';

        return back()->with('success', "Departman başarıyla {$status} duruma getirildi.");
    }
}
