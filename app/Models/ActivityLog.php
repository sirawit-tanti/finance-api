<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model',
        'model_id',
        'description',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];
}