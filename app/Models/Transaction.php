<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Category;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'amount',
        'type'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeOwnedBy($query, $userId)
    {
        return $query->where(
            'user_id',
            $userId
        );
    }

    public function scopeType($query, $type)
    {
        return $query->where(
            'type',
            $type
        );
    }
}