<?php

namespace App\Filters;

use App\Models\Db\User;
use App\Services\Mnabialek\LaravelEloquentFilter\Filters\SimpleQueryFilter;

class TimeTrackingUserFilter extends SimpleQueryFilter
{
    use EmptyFieldSearch;

    const EMPTY = 'empty';

    protected $simpleFilters = [
        'id',
        'integration_id',
        'external_user_id',
    ];

    protected $simpleSorts = [
        'id',
        'integration_id',
        'user_id',
        'external_user_id',
        'external_user_email',
        'external_user_name',
        'created_at',
        'updated_at',
    ];

    protected function applyUserId($value)
    {
        $this->addEmptyFieldQueries('user_id', $value);
    }

    protected function applyExternalUserName($value)
    {
        $this->query->where('external_user_name', 'LIKE', '%' . $value . '%');
    }

    protected function applyExternalUserEmail($value)
    {
        $this->query->where('external_user_email', 'LIKE', '%' . $value . '%');
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

        $this->query = $this->query->with('user');
    }
}
