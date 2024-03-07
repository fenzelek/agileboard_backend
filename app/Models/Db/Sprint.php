<?php

namespace App\Models\Db;

use App\Models\Db\Integration\TimeTracking\Activity;

/**
 * @property string $name
 * @property string $status
 */
class Sprint extends Model
{
    /**
     * Statuses sprints.
     */
    const INACTIVE = 'inactive';
    const ACTIVE = 'active';
    const PAUSED = 'paused';
    const CLOSED = 'closed';

    /**
     * @inheritdoc
     */
    protected $guarded = [
    ];

    protected $dates = [
        'planned_activation', 'planned_closing', 'activated_at', 'closed_at', 'paused_at', 'resumed_at',
    ];

    // Relations

    /**
     * Sprint has one project.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Sprint has multiple tickets.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Sprint has multiple time tracking activities entries.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function timeTrackingActivities()
    {
        return $this->hasManyThrough(Activity::class, Ticket::class);
    }

    /**
     * Sprint has multiple entries in time tracking general summary (in fact it will be one only).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function timeTrackingGeneralSummary()
    {
        return $this->timeTrackingActivities()
            ->selectRaw('sprint_id, SUM(activity) As activity_sum, SUM(tracked) as tracked_sum')
            ->groupBy('sprint_id');
    }

    /**
     * Sprint has multiple entries in tickets general summary (in fact it will be one only).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ticketsGeneralSummary()
    {
        return $this->tickets()
            ->selectRaw('sprint_id, COUNT(tickets.id)AS tickets_count, SUM(estimate_time) AS tickets_estimate_time')
            ->groupBy('sprint_id');
    }
}
