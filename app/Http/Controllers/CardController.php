<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Column;
use App\Models\CardMovement; 
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function store(Request $request, Column $column)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'priority' => 'nullable|string'
        ]);

        $card = $column->cards()->create([
            'title' => $request->title,
            'priority' => $request->priority ?? 'Normal',
            'order' => $column->cards()->count(),
        ]);

        $card->movements()->create([
            'column_id' => $column->id,
            'user_id' => auth()->id(),
        ]);

        return back();
    }

    public function update(Request $request, Card $card)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'column_id' => 'sometimes|required|exists:columns,id',
            'priority' => 'sometimes|required|string',
            'is_blocked' => 'sometimes|boolean',
            'block_reason' => 'nullable|string'
        ]);

        $oldColumnId = $card->column_id;

        $card->update($request->only([
            'title', 
            'column_id', 
            'priority', 
            'is_blocked', 
            'block_reason'
        ]));

        if ($request->has('column_id') && $oldColumnId != $request->column_id) {
            CardMovement::create([
                'card_id' => $card->id,
                'column_id' => $request->column_id,
                'user_id' => auth()->id(),
            ]);
        }

        return back();
    }

    public function destroy(Card $card)
    {
        if ($card->column->board->user_id === auth()->id()) {
            $card->delete();
        }
        return back();
    }
}