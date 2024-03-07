<?php

namespace App\Models\Db\TimeTracker;

use App\Models\Db\Model;
use App\Models\Db\Project;
use App\Models\Db\Project as ModelProject;
use App\Models\Db\Ticket;
use App\Models\Db\User as ModelUser;
use App\Services\Mnabialek\LaravelEloquentFilter\Traits\Filterable;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;

class Frame extends Model
{
    use Filterable, SpatialTrait;

    /**
     * @inheritdoc
     */
    protected $table = 'time_tracker_frames';

    /**
     * @inheritdoc
     */
    protected $guarded = [];

    protected $casts = [
        'screens' => 'array',
        'from' => 'datetime',
        'to' => 'datetime',
    ];

    protected $spatialFields = [
        'coordinates',
    ];

    /**
     * Time tracking activity belongs to single user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(ModelUser::class, 'user_id');
    }

    /**
     * Time tracking activity belongs to single project.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|Project
     */
    public function project()
    {
        return $this->belongsTo(ModelProject::class, 'project_id');
    }

    /**
     * Time tracking activity belongs to single ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function screens()
    {
        return $this->morphMany(ActivityFrameScreen::class, 'screenable')->with('screen');
    }

    /**
     * @return \App\Models\Db\Company|null
     */
    public function getCompany()
    {
        /**
         * @var Project|null $project
         */
        $project = $this->project()->with('company')->first();

        return empty($project) ? null : $project->company;
    }
}
