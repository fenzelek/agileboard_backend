<?php

namespace App\Filters;

use App\Http\Requests\Request;
use App\Models\Db\Project;
use App\Models\Db\User;
use App\Services\Mnabialek\LaravelEloquentFilter\Filters\SimpleQueryFilter;

class TimeTrackingActivityFilter extends SimpleQueryFilter
{
    use EmptyFieldSearch;

    const EMPTY = 'empty';

    protected $simpleFilters = [
        'id',
        'integration_id',
        'external_activity_id',
        'time_tracking_user_id',
        'time_tracking_project_id',
        'time_tracking_note_id',
    ];

    protected $simpleSorts = [
        'id',
        'integration_id',
        'user_id',
        'project_id',
        'ticket_id',
        'external_activity_id',
        'time_tracking_user_id',
        'time_tracking_project_id',
        'time_tracking_note_id',
        'comment',
        'utc_started_at',
        'utc_finished_at',
        'tracked',
        'activity',
        'created_at',
        'updated_at',
    ];

    protected function applyUserId($value)
    {
        $this->addEmptyFieldQueries('user_id', $value);
    }

    protected function applyProjectId($value)
    {
        $this->addEmptyFieldQueries('project_id', $value);
    }

    protected function applyTicketId($value)
    {
        $this->addEmptyFieldQueries('ticket_id', $value);
    }

    protected function applyExternalUserId($value)
    {
        $this->query->whereHas('timeTrackingUser', function ($q) use ($value) {
            $q->where('external_user_id', $value);
        });
    }

    protected function applyMinUtcStartedAt($value)
    {
        $this->query->where('utc_started_at', '>=', $value);
    }

    protected function applyMaxUtcStartedAt($value)
    {
        $this->query->where('utc_started_at', '<=', $value);
    }

    protected function applyMinUtcFinishedAt($value)
    {
        $this->query->where('utc_finished_at', '>=', $value);
    }

    protected function applyMaxUtcFinishedAt($value)
    {
        $this->query->where('utc_finished_at', '<=', $value);
    }

    protected function applyMinTracked($value)
    {
        $this->query->where('tracked', '>=', $value);
    }

    protected function applyMaxTracked($value)
    {
        $this->query->where('tracked', '<=', $value);
    }

    protected function applyMinActivityLevel($value)
    {
        $this->query->whereRaw('activity / tracked >= ?', $value / 100);
    }

    protected function applyMaxActivityLevel($value)
    {
        $this->query->whereRaw('activity / tracked <= ?', $value / 100);
    }

    protected function applyComment($value)
    {
        $this->query->where('comment', 'LIKE', '%' . $value . '%');
    }

    protected function applyTimeTrackingNoteContent($value)
    {
        $this->query->whereHas('timeTrackingNote', function ($q) use ($value) {
            $q->where('content', 'LIKE', '%' . $value . '%');
        });
    }

    protected function applySource($value)
    {
        $this->query->whereHas('integration.provider', function ($q) use ($value) {
            $q->where('slug', $value);
        });
    }

    /**
     * @inheritdoc
     */
    protected function applyDefaultFilters()
    {
        /** @var User $user */
        $user = $this->app['auth']->user();

        /** @var Request $request */
        $request = $this->app['request'];

        // first make sure we display entries only for current company
        $this->query = $this->query->whereHas('integration', function ($q) use ($user) {
            $q->where('company_id', $user->getSelectedCompanyId());
        });

        $this->query =
            $this->query->with(
                'user',
                'project',
                'ticket',
                'timeTrackingUser',
                'timeTrackingNote'
            );

        // if user is company owner or admin we don't add any limits - they can see their own or
        // other company users entries
        if (! $this->isProjectSelected() && $user->isOwnerOrAdmin()) {
            return;
        }

        // if project is selected and user is manager in project the same will apply - user can see
        // his own entries and other project user entries
        if ($this->isProjectSelected() &&
            $user->managerInProject(Project::find($request->input('project_id')))) {
            return;
        }

        // otherwise it means it's regular user - so they should be able to see only their own entries
        $this->query = $this->query->where('user_id', $user->id);
    }

    protected function applyDefaultSorts()
    {
        $this->query = $this->query->orderBy('utc_started_at');
    }

    protected function isProjectSelected()
    {
        /** @var Request $request */
        $request = $this->app['request'];

        if (! $request->has('project_id')) {
            return false;
        }

        if ($request->input('project_id') == static::EMPTY) {
            return false;
        }

        return true;
    }
}
