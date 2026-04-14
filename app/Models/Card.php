<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'column_id', 'order', 'priority', 'is_blocked', 'block_reason'];

    protected $casts = [
        'is_blocked' => 'boolean',
    ];
    public function column()
    {
        return $this->belongsTo(Column::class);
    }

    public function movements()
    {
        return $this->hasMany(CardMovement::class);
    }
}