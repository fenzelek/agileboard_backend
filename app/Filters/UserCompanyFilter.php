<?php

namespace App\Filters;

use App\Services\Mnabialek\LaravelEloquentFilter\Filters\SimpleQueryFilter;

class UserCompanyFilter extends SimpleQueryFilter
{
    use UserSearch;
}
