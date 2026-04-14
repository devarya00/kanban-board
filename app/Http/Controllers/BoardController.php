<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Http\Request;
use Inertia\Inertia;


class BoardController extends Controller
{
    public function index()
    {
        $boards = Board::where('user_id', auth()->id())
            ->with(['columns.cards'])
            ->get();
        return Inertia::render('Kanban/Index', [
            'boards' => $boards
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        Board::create([
            'title' => $request->title,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('kanban.index');
    }

    public function destroy(Board $board)
    {
        if ($board->user_id === auth()->id()) {
            $board->delete();
        }

        return back();
    }
}
