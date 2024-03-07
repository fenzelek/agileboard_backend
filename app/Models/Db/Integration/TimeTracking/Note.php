<?php

namespace App\Models\Db\Integration\TimeTracking;

use App\Models\Db\Integration\Integration;
use App\Models\Db\Model;

class Note extends Model
{
    /**
     * @inheritdoc
     */
    protected $table = 'time_tracking_notes';

    /**
     * @inheritdoc
     */
    protected $guarded = [];

    /**
     * Time tracking note belongs to single integration.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function integration()
    {
        return $this->belongsTo(Integration::class);
    }

    /**
     * Time tracking note belongs to single external project.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function externalProject()
    {
        return $this->belongsTo(Project::class, 'external_project_id');
    }

    /**
     * Time tracking note belongs to single external user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function externalUser()
    {
        return $this->belongsTo(User::class, 'external_user_id');
    }
}
