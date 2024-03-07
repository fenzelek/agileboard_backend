<?php

namespace App\Modules\Company\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Resources\CompanyInInvitationsList;
use App\Models\Db\Company;
use App\Models\Db\Invitation;
use App\Models\Db\User;
use App\Models\Other\ModuleType;
use App\Modules\Company\Http\Requests\Invite as InviteRequest;
use App\Modules\Company\Services\CompanyModuleUpdater;
use App\Modules\Company\Services\Invite as InviteService;
use App\Modules\Company\Http\Requests\AcceptInvitation;
use App\Modules\Company\Http\Requests\RejectInvitation;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvitationController
{
    /**
     * Invite user to company.
     *
     * @param InviteRequest $request
     * @param InviteService $service
     * @param int $companyId
     *
     * @return JsonResponse
     */
    public function store(InviteRequest $request, InviteService $service, $companyId)
    {
        if ($service->cantAddUsers($companyId)) {
            return ApiResponse::responseError(ErrorCode::PACKAGE_LIMIT_REACHED, 409);
        }

        $result = $service->add($request, $companyId);

        if (! $result['status']) {
            return ApiResponse::responseError(
                ErrorCode::COMPANY_INVITATION_ALREADY_ASSIGNED,
                Response::HTTP_CONFLICT
            );
        }

        // check invitations settings
        $invite_enabled = auth()->user()->selectedCompany()->appSettings(ModuleType::GENERAL_INVITE_ENABLED);

        if ($invite_enabled) {
            return ApiResponse::responseOk([], 201);
        }

        return ApiResponse::responseOk($result['user']->fresh(), 201);
    }

    /**
     * Accept invitation.
     *
     * @param AcceptInvitation $request
     * @param InviteService $service
     * @param CompanyModuleUpdater $updater
     * @param Company $company
     *
     * @return Invitation|JsonResponse
     */
    public function accept(AcceptInvitation $request, InviteService $service, CompanyModuleUpdater $updater, Company $company)
    {
        $invitation = $this->getInvitation($request);
        if (! $invitation instanceof Invitation) {
            return $invitation;
        }

        $result = $service->accept($invitation, $request);

        if (! $result) {
            return ApiResponse::responseError(
                ErrorCode::COMPANY_INVITATION_ALREADY_ASSIGNED,
                Response::HTTP_CONFLICT
            );
        }

        //blockade company
        $updater->setCompany($company->find($invitation->company_id));
        $updater->updateBlockadedCompany(ModuleType::GENERAL_MULTIPLE_USERS);

        return ApiResponse::responseOk([], 200);
    }

    /**
     * Reject invitation.
     *
     * @param RejectInvitation $request
     * @param InviteService $service
     *
     * @return JsonResponse
     */
    public function reject(RejectInvitation $request, InviteService $service)
    {
        $invitation = $this->getInvitation($request);
        if (! $invitation instanceof Invitation) {
            return $invitation;
        }
        $service->reject($invitation);

        return ApiResponse::responseOk([], 200);
    }

    /**
     * Get pending invitations for current user.
     *
     * @param Guard $guard
     *
     * @return JsonResponse
     */
    public function currentIndex(Request $request, Guard $guard)
    {
        $invitations = Invitation::where('email', $guard->user()->email)
            ->pending()->with('company.vatinPrefix', 'role')->orderBy('created_at', 'ASC')->get();
        if ($request->input('active') == 1) {
            $invitations = $invitations->where('expiration_time', '>', Carbon::now())->values();
        }

        return ApiResponse::transResponseOk($invitations, 200, [
            Company::class => CompanyInInvitationsList::class,
        ]);
    }

    /**
     * Get invitation. In case of any error return error response.
     *
     * @param Request $request
     *
     * @return Invitation|JsonResponse
     */
    protected function getInvitation(Request $request)
    {
        /** @var Invitation $invitation */
        $invitation = Invitation::where('unique_hash', $request->input('token'))->firstOrFail();
        if (! $invitation->isPending()) {
            return ApiResponse::responseError(
                ErrorCode::COMPANY_INVITATION_NOT_PENDING,
                Response::HTTP_CONFLICT
            );
        }
        if ($invitation->isExpired()) {
            return ApiResponse::responseError(
                ErrorCode::COMPANY_INVITATION_EXPIRED,
                Response::HTTP_CONFLICT
            );
        }

        return $invitation;
    }
}
