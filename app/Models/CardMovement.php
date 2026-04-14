<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardMovement extends Model
{
    use HasFactory;

    protected $fillable = ['card_id', 'column_id', 'user_id'];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    public function column()
    {
        return $this->belongsTo(Column::class);
    }
}