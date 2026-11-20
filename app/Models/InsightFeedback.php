<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsightFeedback extends Model
{
    protected $table = 'insight_feedbacks';

    protected $fillable = [
        'user_id',
        'room_id',
        'insight_key',
        'helpful',
        'applied',
    ];

    protected function casts(): array
    {
        return [
            'helpful' => 'boolean',
            'applied' => 'boolean',
        ];
    }
}
