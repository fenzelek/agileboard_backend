<?php

namespace App\Models\Db\Integration\TimeTracking;

use App\Models\Db\Integration\Integration;
use App\Models\Db\Model;
use App\Models\Db\Project as ProjectModel;
use App\Services\Mnabialek\LaravelEloquentFilter\Traits\Filterable;

/**
 * @property string $name
 */
class Project extends Model
{
    use Filterable;
    /**
     * @inheritdoc
     */
    protected $table = 'time_tracking_projects';

    /**
     * @inheritdoc
     */
    protected $guarded = [];

    /**
     * Time tracking project belongs to single integration.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function integration()
    {
        return $this->belongsTo(Integration::class);
    }

    /**
     * Time tracking project belongs to single system project.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(ProjectModel::class);
    }

    /**
     * Time tracking project has multiple time tracking activities.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activities()
    {
        return $this->hasMany(Activity::class, 'time_tracking_project_id');
    }
}
