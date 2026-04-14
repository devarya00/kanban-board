<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Http\Request;

class ColumnController extends Controller
{
    public function store(Request $request, Board $board)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'wip_limit' => 'nullable|integer|min:1',
            'policy' => 'nullable|string',
        ]);

        $board->columns()->create([
            'title' => $request->title,
            'wip_limit' => $request->wip_limit,
            'policy' => $request->policy,
            'order' => $board->columns()->count(),
        ]);

        return back();
    }

}
