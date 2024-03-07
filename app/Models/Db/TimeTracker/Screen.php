<?php

namespace App\Models\Db\TimeTracker;

use App\Models\Db\Model;
use App\Models\Db\User;

class Screen extends Model
{
    /**
     * @inheritdoc
     */
    protected $table = 'time_tracker_screens';

    /**
     * @inheritdoc
     */
    protected $fillable = [
        'name',
        'user_id',
        'thumbnail_link',
        'url_link',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
