<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Department;
use Illuminate\Support\Facades\Gate;

class ReferenceDataController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Category::class);

        $categories = Category::withCount('kaizens')
            ->orderBy('is_active', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        $departments = Department::withCount([
            'kaizens',
            'users',
            'users as active_users_count' => function ($query) {
                $query->where('is_active', true);
            },
        ])
            ->orderBy('is_active', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        return view('settings.reference-data.index', compact('categories', 'departments'));
    }
}
