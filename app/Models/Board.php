<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'user_id', 'urgent_threshold_days', 'is_favorite'];

    protected $casts = [
        'is_favorite' => 'boolean',
    ];

    public function columns() 
    {
        return $this->hasMany(Column::class)->orderBy('order');
    }
}
