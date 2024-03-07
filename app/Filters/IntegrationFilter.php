<?php

namespace App\Filters;

use App\Models\Db\User;
use App\Services\Mnabialek\LaravelEloquentFilter\Filters\SimpleQueryFilter;

class IntegrationFilter extends SimpleQueryFilter
{
    use EmptyFieldSearch;

    const EMPTY = 'empty';

    protected $simpleFilters = [
        'id',
        'integration_provider_id',
        'active',
    ];

    protected $simpleSorts = [
        'id',
        'company_id',
        'integration_provider_id',
        'active',
        'created_at',
        'updated_at',
    ];

    /**
     * @inheritdoc
     */
    protected function applyDefaultFilters()
    {
        /** @var User $user */
        $user = $this->app['auth']->user();

        // make sure we display entries only for current company
        $this->query = $this->query->where('company_id', $user->getSelectedCompanyId());

        $this->query = $this->query->with('provider');
    }
}
