<?php

namespace App\Filters;

use App\Models\Db\User;
use App\Services\Mnabialek\LaravelEloquentFilter\Filters\SimpleQueryFilter;

class TimeTrackingProjectFilter extends SimpleQueryFilter
{
    use EmptyFieldSearch;

    const EMPTY = 'empty';

    protected $simpleFilters = [
        'id',
        'integration_id',
        'external_project_id',
    ];

    protected $simpleSorts = [
        'id',
        'integration_id',
        'project_id',
        'external_project_id',
        'external_project_name',
        'created_at',
        'updated_at',
    ];

    protected function applyProjectId($value)
    {
        $this->addEmptyFieldQueries('project_id', $value);
    }

    protected function applyExternalProjectName($value)
    {
        $this->query->where('external_project_name', 'LIKE', '%' . $value . '%');
    }

    /**
     * @inheritdoc
     */
    protected function applyDefaultFilters()
    {
        /** @var User $user */
        $user = $this->app['auth']->user();

        // make sure we display entries only for current company
        $this->query = $this->query->whereHas('integration', function ($q) use ($user) {
            $q->where('company_id', $user->getSelectedCompanyId());
        });

        $this->query = $this->query->with('project');
    }
}
