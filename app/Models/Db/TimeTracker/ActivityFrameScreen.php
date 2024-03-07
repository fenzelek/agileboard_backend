<?php

namespace App\Models\Db\TimeTracker;

use App\Models\Db\Model;

class ActivityFrameScreen extends Model
{
    public $timestamps = false;
    /**
     * @inheritdoc
     */
    protected $table = 'time_tracker_activity_frame_screen';

    protected $fillable = [
        'screenable_id',
        'screenable_type',
        'screen_id',
    ];

    /**
     * Get all of the owning screenable models.
     */
    public function screenable()
    {
        return $this->morphTo();
    }

    public function screen()
    {
        return $this->belongsTo(Screen::class, 'screen_id');
    }
}
