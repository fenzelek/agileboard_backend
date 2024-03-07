<?php

namespace App\Modules\User\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Db\Role;

class RoleController extends Controller
{
    /**
     * Get list of all roles.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return ApiResponse::responseOk(Role::orderBy('id')->get());
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function company()
    {
        $id = auth()->user()->getSelectedCompanyId();

        $roles = Role::orderBy('id')
            ->whereHas('companies', function ($q) use ($id) {
                $q->where('id', $id);
            })
            ->get();

        return ApiResponse::responseOk($roles);
    }
}
