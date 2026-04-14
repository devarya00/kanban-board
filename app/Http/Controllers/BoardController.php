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
            'urgent_threshold_days' => 'required|integer|min:1',
            'todo_wip' => 'nullable|integer|min:1',
            'doing_wip' => 'nullable|integer|min:1',
            'done_wip' => 'nullable|integer|min:1',
        ]);

        $board = Board::create([
            'title' => $request->title,
            'urgent_threshold_days'=> $request->urgent_threshold_days,
            'user_id' => auth()->id(),
        ]);

        $board->columns()->createMany([
            [
                'title' => 'To Do', 
                'order' => 0, 
                'wip_limit' => $request->todo_wip, 
                'policy' => 'Tasks ready to be started.'
            ],
            [
                'title' => 'Doing', 
                'order' => 1, 
                'wip_limit' => $request->doing_wip ?? 3, 
                'policy' => 'Tasks in progress. Focus on finishing before pulling new ones.'
            ],
            [
                'title' => 'Done', 
                'order' => 2, 
                'wip_limit' => $request->done_wip, 
                'policy' => 'Tasks 100% completed and validated.'
            ],
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

    public function toggleFavorite(Board $board)
    {
        if ($board->user_id === auth()->id()) {
            $board->update(['is_favorite' => !$board->is_favorite]);
        }
        return back();
    }
}