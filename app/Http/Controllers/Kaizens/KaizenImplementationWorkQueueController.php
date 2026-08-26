<?php

namespace App\Http\Controllers\Kaizens;

use App\Http\Controllers\Controller;
use App\Queries\KaizenImplementationWorkQueueQuery;
use Illuminate\Http\Request;

class KaizenImplementationWorkQueueController extends Controller
{
    public function __construct(
        private readonly KaizenImplementationWorkQueueQuery $query
    ) {
    }

    public function index(Request $request)
    {
        abort_if(! $request->user()->is_active, 403);

        $kaizens = $this->query->execute($request->user());

        return view('kaizens.implementation.work_queue', compact('kaizens'));
    }
}
