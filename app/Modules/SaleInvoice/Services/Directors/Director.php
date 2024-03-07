<?php

namespace App\Modules\SaleInvoice\Services\Directors;

use App\Http\Requests\Request;
use App\Models\Db\User;

abstract class Director
{
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var User
     */
    protected $user;
}
