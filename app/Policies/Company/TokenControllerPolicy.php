<?php

namespace App\Policies\Company;

use App\Policies\BasePolicy;

class TokenControllerPolicy extends BasePolicy
{
    protected $group = 'company-token';
}
