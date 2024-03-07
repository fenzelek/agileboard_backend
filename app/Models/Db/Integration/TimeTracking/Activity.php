<?php

namespace App\Models\Db\Integration\TimeTracking;

use App\Models\Db\Integration\Integration;
use App\Models\Db\Model;
use App\Models\Db\Project as ModelProject;
use App\Models\Db\Sprint;
use App\Models\Db\Ticket;
use App\Models\Db\TimeTracker\ActivityFrameScreen;
use App\Models\Db\User as ModelUser;
use App\Services\Mnabialek\LaravelEloquentFilter\Traits\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ?int $ticket_id
 * @property ?int $user_id
 * @property ?ModelUser $user
 * @property ?Project $project
 * @property ?Ticket $ticket
 * @property string $comment
 * @property ?Carbon $utc_started_at
 * @property ?Carbon $utc_finished_at
 * @property int $tracked
 * @property bool $manual
 * @method static Builder|Activity newQuery()
 * @method static Builder|Activity active()
 * @method static Builder|Activity companyId(int $company_id)
 */
class Activity extends Model
{
    use Filterable, SoftDeletes;

    private const MANUAL = 'manual';
    /**
     * @inheritdoc
     */
    protected $table = 'time_tracking_activities';

    /**
     * @inheritdoc
     */
    protected $guarded = [];

    /**
     * @inheritdoc
     */
    protected $dates = [
        'utc_started_at',
        'utc_finished_at',
    ];

    protected $casts = [
        'screens' => 'array',
    ];

    /**
     * Time tracking activity belongs to single integration.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function integration()
    {
        return $this->belongsTo(Integration::class);
    }

    /**
     * Time tracking activity belongs to single time tracking user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function timeTrackingUser()
    {
        return $this->belongsTo(User::class, 'time_tracking_user_id');
    }

    /**
     * Time tracking activity belongs to single time tracking project.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function timeTrackingProject()
    {
        return $this->belongsTo(Project::class, 'time_tracking_project_id');
    }

    /**
     * Time tracking activity belongs to single time tracking note.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function timeTrackingNote()
    {
        return $this->belongsTo(Note::class, 'time_tracking_note_id');
    }

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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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

    public function scopeCompanyId($query, $company_id)
    {
        $query->whereHas('project', function (Builder $query) use ($company_id) {
            $query->companyId($company_id);
        });
    }

    public function scopeWhenProjectId(Builder $query, ?int $project_id)
    {
        $query->when($project_id,
            fn(Builder $query) => $query->where('project_id', '=', $project_id));
    }

    public function scopeforDate(Builder $query, string $date)
    {
        $query->where('utc_started_at', '>=', Carbon::parse($date)->startOfDay())
            ->where('utc_finished_at', '<=', Carbon::parse($date)->endOfDay());
    }

    /**
     * Get activity level.
     *
     * @return float
     */
    public function getActivityLevel()
    {
        return activity_level($this->tracked, $this->activity);
    }

    /**
     * Get activity level for summary fields.
     *
     * @return float
     */
    public function getActivitySummaryLevel()
    {
        return activity_level($this->tracked_sum, $this->activity_sum);
    }

    /**
     * Get activity is manual.
     *
     * @return bool
     */
    public function getManualAttribute()
    {
        return str_starts_with($this->external_activity_id, self::MANUAL) ? true : false;
    }

    /**
     * Verify whether it has been locked.
     *
     * @return bool
     */
    public function isLocked()
    {
        return $this->locked_user_id ? true : false;
    }

    public function screens()
    {
        return $this->morphMany(ActivityFrameScreen::class, 'screenable')->with('screen');
    }

    public function getScreens()
    {
        return $this->getRelation('screens');
    }

    public function scopeOnlyNoTrashed(Builder $builder)
    {
        $builder->whereNull('deleted_at');
    }
}
