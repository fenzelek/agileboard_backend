<?php

namespace App\Modules\Company\Http\Controllers;

use App\Models\Db\Company;
use App\Models\Db\ProjectUser;
use App\Models\Other\ModuleType;
use App\Modules\Company\Services\CompanyModuleUpdater;
use DB;
use App\Filters\UserCompanyFilter;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Company\Http\Requests\User as RequestUser;
use App\Models\Db\User;
use App\Models\Db\UserCompany;
use App\Http\Resources\UserCompanyList;
use App\Models\Other\UserCompanyStatus;
use App\Modules\Company\Http\Requests\UserUpdate;
use Illuminate\Contracts\Auth\Guard;

class UserController extends Controller
{
    /**
     * Get list of users in current company.
     *
     * @param RequestUser $request
     * @param Guard $auth
     * @param UserCompanyFilter $filter
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(RequestUser $request, Guard $auth, UserCompanyFilter $filter)
    {
        $company_status = (int) $request->input('company_status');

        $users_company = UserCompany::filtered($filter)->with('user')->inCompany($auth->user());

        if (! empty($company_status)) {
            $users_company = $users_company->where('status', $company_status);
        }

        $users_company = $users_company->orderBy('user_id', 'asc')->get();

        return ApiResponse::transResponseOk($users_company, 200, [
            UserCompany::class => UserCompanyList::class,
        ]);
    }

    /**
     * Update user role in company.
     *
     * @param UserUpdate $request
     * @param User $user
     * @return mixed
     */
    public function update(UserUpdate $request, User $user)
    {
        return DB::transaction(function () use ($user, $request) {
            $user = $user->findOrFail($request->input('user_id'));
            $company_id = auth()->user()->getSelectedCompanyId();

            $user->companies()->detach($company_id);

            $user->companies()->attach($company_id, [
                'role_id' => $request->input('role_id'),
                'status' => UserCompanyStatus::APPROVED,
            ]);

            return ApiResponse::responseOk([], 200);
        });
    }

    /**
     * Remove user from company.
     *
     * @param $id
     * @param Guard $auth
     * @param ProjectUser $projectUser
     * @param Company $company
     * @param CompanyModuleUpdater $updater
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id, Guard $auth, ProjectUser $projectUser, Company $company, CompanyModuleUpdater $updater)
    {
        if ($id == $auth->user()->id) {
            return ApiResponse::responseOk([], 420);
        }

        return DB::transaction(function () use ($id, $auth, $projectUser, $company, $updater) {
            User::findOrFail($id)->userCompanies()
                ->inCompany($auth->user())
                ->update(['status' => UserCompanyStatus::DELETED]);

            $company_id = auth()->user()->getSelectedCompanyId();

            $projectUser->where('user_id', $id)
                ->whereHas('project', function ($q) use ($company_id) {
                    $q->where('company_id', $company_id);
                })
                ->delete();

            //blockade company
            $updater->setCompany($company->find($auth->user()->getSelectedCompanyId()));
            $updater->updateBlockadedCompany(ModuleType::GENERAL_MULTIPLE_USERS);

            return ApiResponse::responseOk([]);
        });
    }
}
