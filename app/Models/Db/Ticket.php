<?php

namespace App\Models\Db;

use App\Interfaces\Interactions\IInteractionable;
use App\Interfaces\CompanyInterface;
use App\Interfaces\Involved\IHasInvolved;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Other\InvolvedSourceType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ?string $name
 * @property ?string $title
 * @property ?string $description
 * @property User $assignedUser
 * @property ?Sprint $sprint
 * @property int $estimate_time
 */
class Ticket extends Model implements IInteractionable, IHasInvolved
{
    use SoftDeletes;

    /**
     * @inheritdoc
     */
    protected $guarded = [
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['scheduled_time_start', 'scheduled_time_end', 'deleted_at'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function files()
    {
        return $this->morphToMany(File::class, 'fileable');
    }

    public function stories()
    {
        return $this->morphToMany(Story::class, 'storyable');
    }

    public function sprint()
    {
        return $this->belongsTo(Sprint::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function type()
    {
        return $this->belongsTo(TicketType::class);
    }

    public function tickets()
    {
        return $this->hasMany(self::class);
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class);
    }

    public function history()
    {
        return $this->hasMany(History::class, 'resource_id');
    }

    /**
     * Ticket might be assigned to single user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_id');
    }

    /**
     * Ticket was reported by single user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reportingUser()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Ticket has multiple time tracking activities.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function timeTrackingActivities()
    {
        return $this->hasMany(Activity::class, 'ticket_id');
    }

    public function involved(): MorphMany
    {
        return $this->morphMany(Involved::class, 'source');
    }

    public function parentTickets()
    {
        return $this->belongsToMany(
            self::class,
            'ticket_ticket',
            'main_ticket_id',
            'sub_ticket_id'
        );
    }

    public function subTickets()
    {
        return $this->belongsToMany(
            self::class,
            'ticket_ticket',
            'sub_ticket_id',
            'main_ticket_id'
        );
    }

    /**
     * Scope a query to only include object in user company.
     *
     * @param Builder $query
     * @param CompanyInterface $object
     *
     * @return Builder
     */
    public function scopeInCompany($query, CompanyInterface $object): Builder
    {
        return $query->whereHas('project', fn ($project) => $project->inCompany($object));
    }

    /**
     * Scope a query to only include object in user company.
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeCreatedInLastThreeMonths($query): Builder
    {
        return $query->whereDate('created_at', '>=', Carbon::now()->subMonth(3));
    }

    /**
     * Scope a query to only include tickets in active sprints.
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeActiveSprint($query): Builder
    {
        return $query->whereHas(
            'sprint',
            fn ($query) => $query->where('status', Sprint::ACTIVE)
        );
    }

    /**
     * Scope a query to only include tickets in no done status.
     *
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopeNotDone($query): Builder
    {
        return $query->whereRaw('tickets.status_id != (SELECT MAX(id) FROM statuses where project_id = tickets.project_id)');
    }

    public function interactions(): MorphMany
    {
        return $this->morphMany(Interaction::class, 'source');
    }

    /**
     * Ticket has multiple entries in time tracking summary.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function timeTrackingSummary()
    {
        return $this->timeTrackingActivities()
            ->selectRaw('user_id, ticket_id, time_tracking_user_id, SUM(activity) As activity_sum, SUM(tracked) as tracked_sum')
            ->groupBy('user_id')
            ->with('user', 'timeTrackingUser')->orderBy('tracked_sum', 'DESC');
    }

    /**
     * Ticket has multiple entries in time tracking general summary (in fact it will be one only).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function timeTrackingGeneralSummary()
    {
        return $this->timeTrackingActivities()
            ->selectRaw('ticket_id, SUM(activity) As activity_sum, SUM(tracked) as tracked_sum')
            ->groupBy('ticket_id');
    }

    public function getSourceType(): string
    {
        return InvolvedSourceType::TICKET;
    }

    public function getSourceId(): int
    {
        return $this->id;
    }
}
