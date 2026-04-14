<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Column;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function store(Request $request, Column $column)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'priority' => 'nullable|string'
        ]);

        $column->cards()->create([
            'title' => $request->title,
            'priority' => $request->priority ?? 'Normal',
            'order' => $column->cards()->count(),
        ]);

        return back();
    }

    public function update(Request $request, Card $card)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'column_id' => 'sometimes|required|exists:columns,id',
            'priority' => 'sometimes|required|string'
        ]);

        $card->update($request->only(['title', 'column_id', 'priority']));

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