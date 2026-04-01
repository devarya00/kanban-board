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
        ]);

        $board->columns()->create([
            'title' => $request->title,
            'order' => $board->columns()->count(),
        ]);

        return back();
    }
}
