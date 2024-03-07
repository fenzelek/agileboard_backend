<?php

namespace App\Models\Db;

class Status extends Model
{
    /**
     * @inheritdoc
     */
    protected $guarded = [
    ];

    public static function lastStatus($project_id)
    {
        return self::where('project_id', $project_id)->orderBy('priority', 'desc')->first();
    }

    public static function firstStatus($project_id)
    {
        return self::where('project_id', $project_id)->orderBy('priority')->first();
    }

    /**
     * Relations.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
