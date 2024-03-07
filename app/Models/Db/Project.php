<?php

namespace App\Models\Db;

use App\Models\CustomCollections\ProjectsCollection;
use App\Models\Db\Integration\TimeTracking\Activity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Project extends Model
{
    use SoftDeletes, Notifiable;

    protected $guarded = [];

    protected $dates = ['closed_at', 'deleted_at'];

    /**
     * Project can be assigned to multiple users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps()
            ->withPivot(['user_id', 'project_id', 'role_id']);
    }

    /**
     * Project is assigned to company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function isOpen()
    {
        return null === $this->closed_at ? true : false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function files()
    {
        return $this->hasMany(File::class);
    }

    /**
     * Project can be assigned to multiple sprints.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sprints()
    {
        return $this->hasMany(Sprint::class);
    }

    /**
     * Project can be assigned to multiple statuses.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function statuses()
    {
        return $this->hasMany(Status::class);
    }

    /**
     * Project can be assigned to multiple tickets.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Project can be assigned to multiple stories.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stories()
    {
        return $this->hasMany(Story::class);
    }

    /**
     * Verify whether short_name of project can be changed.
     *
     * @return bool
     */
    public function hasEditableShortName()
    {
        return $this->tickets()->withTrashed()->count() == 0;
    }

    /**
     * Project has multiple time tracking activities entries.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function timeTrackingActivities()
    {
        return $this->hasMany(Activity::class, 'project_id');
    }

    public function permission()
    {
        return $this->hasOne(ProjectPermission::class, 'project_id');
    }

    /**
     * Project has multiple entries in time tracking summary.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function timeTrackingSummary()
    {
        return $this->timeTrackingActivities()
            ->selectRaw('user_id, project_id, time_tracking_user_id, SUM(activity) As activity_sum, SUM(tracked) as tracked_sum')
            ->groupBy('user_id')
            ->with('user', 'timeTrackingUser')->orderBy('tracked_sum', 'DESC');
    }

    /**
     * Get Slack channel for self Slack notification channel.
     *
     * @return string
     */
    public function routeNotificationForSlack()
    {
        return $this->slack_webhook_url;
    }

    /**
     * Get projects which doesn't have closed date.
     *
     * @return mixed
     */
    public function scopeOpen($query)
    {
        return $query->whereNull('closed_at');
    }

    /**
     * Get projects which have assign users from parameter.
     *
     * @param $query
     * @param $user_ids
     * @return mixed
     */
    public function scopeHasUsers($query, $user_ids)
    {
        return $query->whereHas('users', function ($query) use ($user_ids) {
            $query->whereIn('user_id', $user_ids);
        });
    }

    /**
     * Get projects which have assign users from parameter.
     *
     * @param $query
     * @param $user_ids
     * @return mixed
     */
    public function scopeParticipedIn($query, $user)
    {
        return $query->whereHas('users', function ($query) use ($user) {
            $query->where('project_user.user_id', $user->id);
        });
    }

    /**
     * Get projects for one company.
     *
     * @param $query
     * @param $company_id
     * @return mixed
     */
    public function scopeForCompany($query, $company_id)
    {
        return $query->where('company_id', $company_id);
    }

    /**
     * Custom projects collection.
     *
     * @param array $models
     * @return ProjectsCollection|\Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new ProjectsCollection($models);
    }

    /**
     * @param User $user
     *
     * @return mixed
     */
    public function getRole(User $user)
    {
        $user_in_project = $this->users()->where('user_id', $user->id)->first();

        return Role::find($user_in_project->pivot->role_id ?? 0);
    }
}
