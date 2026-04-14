<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Card;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OverviewController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $boards = Board::where('user_id', $userId)
            ->with('columns.cards')
            ->get()
            ->map(function ($board) {
                $totalTasks = 0;
                $completedTasks = 0;

                foreach ($board->columns as $column) {
                    $count = $column->cards->count();
                    $totalTasks += $count;
                    
                    if (str_contains(strtolower($column->title), 'done') || str_contains(strtolower($column->title), 'conclu')) {
                        $completedTasks += $count;
                    }
                }

                $board->stats = [
                    'total' => $totalTasks,
                    'completed' => $completedTasks,
                    'pending' => $totalTasks - $completedTasks,
                    'progress' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0
                ];

                return $board;
            });

        $priorityData = Card::whereHas('column.board', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get();

        return Inertia::render('Kanban/Overview', [
            'boards' => $boards,
            'priorityData' => $priorityData 

        ]);
    }
}