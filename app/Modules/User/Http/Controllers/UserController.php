<?php

namespace App\Modules\User\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\CurrentUserCompanies;
use App\Models\Db\Company;
use App\Models\Db\User;
use App\Models\Other\UserCompanyStatus;
use App\Modules\User\Http\Requests\CreateUser;
use App\Modules\User\Http\Requests\UpdateUser;
use Illuminate\Contracts\Auth\Guard;
use App\Modules\User\Services\User as UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Filesystem\FilesystemManager as Filesystem;

class UserController extends Controller
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * UserController constructor.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Get list of all allowed users.
     *
     * @return JsonResponse
     */
    public function index()
    {
        return ApiResponse::responseOk(User::allowed()->orderBy('id')->get());
    }

    /**
     * Creates new user.
     *
     * @param CreateUser $request
     * @param UserService $service
     *
     * @return JsonResponse
     */
    public function store(CreateUser $request, UserService $service)
    {
        $user = $service->create($request);

        return ApiResponse::responseOk($user, 201);
    }

    /**
     * Return current user data.
     *
     * @param Guard $auth
     *
     * @return JsonResponse
     */
    public function current(Guard $auth)
    {
        $user = $auth->user();

        /*
         * @see \Illuminate\Database\Eloquent\Model;
         */
        //$user->load('selectedUserCompany.role', 'selectedUserCompany.company');

        $selected_company = $user->selectedUserCompany;

        return ApiResponse::responseOk($user);
    }

    /**
     * Return list of companies for current user.
     *
     * @param Guard $auth
     *
     * @return JsonResponse
     */
    public function companies(Guard $auth)
    {
        $companies = $auth->user()->companies()->wherePivot('status', UserCompanyStatus::APPROVED)
            ->with('vatinPrefix')
            ->get();

        return ApiResponse::transResponseOk($companies, 200, [
                Company::class => CurrentUserCompanies::class,
            ]);
    }

    /**
     * @param UpdateUser $request
     * @param UserService $service
     * @param $id
     *
     * @return JsonResponse
     */
    public function update(UpdateUser $request, UserService $service, $id)
    {
        $user = User::findOrFail($id);
        if ($request->input('password') && ! $service->checkPassword($user, $request)) {
            return ApiResponse::responseError(ErrorCode::PASSWORD_INVALID_PASSWORD, 422);
        }

        $service->updateData($user, $request);

        return ApiResponse::responseOK([]);
    }

    /**
     * Get user avatar.
     *
     * @param string $avatar
     *
     * @return Filesystem
     */
    public function getAvatar($avatar)
    {
        $file = $this->filesystem->disk('avatar')->get($avatar);
        $mimeType = $this->filesystem->disk('avatar')->mimeType($avatar);

        return response($file, 200)->header('Content-Type', $mimeType);
    }
}
