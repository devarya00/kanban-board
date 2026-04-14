<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Inertia\Inertia;

class OverviewController extends Controller
{
    public function index()
    {
        $boards = Board::where('user_id', auth()->id())
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
                ];

                return $board;
            });

        return Inertia::render('Kanban/Overview', [
            'boards' => $boards
        ]);
    }
}