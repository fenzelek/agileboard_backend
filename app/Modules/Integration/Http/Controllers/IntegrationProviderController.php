<?php

namespace App\Modules\Integration\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Db\Integration\IntegrationProvider;

class IntegrationProviderController
{
    public function index()
    {
        return ApiResponse::responseOk(IntegrationProvider::oldest('id')->get());
    }
}
