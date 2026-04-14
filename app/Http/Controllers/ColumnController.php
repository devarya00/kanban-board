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
        ]);

        $board->columns()->create([
            'title' => $request->title,
            'wip_limit' => $request->wip_limit,
            'order' => $board->columns()->count(),
        ]);

        return back();
    }

}
