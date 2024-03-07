<?php

namespace App\Modules\Project\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectCompanyInfo;
use App\Http\Resources\ProjectSimply;
use App\Http\Resources\ProjectWithDetails;
use App\Http\Resources\ProjectWithDetailsForAdmin;
use App\Http\Resources\TimeTracking\ActivitySummary;
use App\Http\Resources\TimeTracking\User as TimeTrackingUserTransformer;
use App\Models\Db\Company;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Integration\TimeTracking\User;
use App\Models\Other\ModuleType;
use App\Modules\Company\Services\CompanyModuleUpdater;
use App\Modules\Project\Http\Requests\ProjectClone;
use App\Modules\Project\Services\Projects;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Modules\Project\Services\Projects as ServiceProjects;
use App\Modules\Project\Http\Requests\ProjectStore;
use App\Modules\Project\Http\Requests\ProjectClose;
use App\Modules\Project\Http\Requests\ProjectIndex;
use App\Modules\Project\Http\Requests\ProjectUpdate;
use App\Models\Db\Project;
use App\Services\Paginator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of the projects.
     *
     * @param ProjectIndex $request
     * @param Paginator $paginator
     * @param ServiceProjects $service
     * @param Guard $auth
     * @return JsonResponse
     */
    public function index(
        ProjectIndex $request,
        Paginator $paginator,
        ServiceProjects $service,
        Guard $auth
    ) {
        $projects_query = $service->filterProjects($request, $auth->user());
        $projects = $paginator->get($projects_query, 'projects.index');

        return ApiResponse::transResponseOk($projects, 200, [
            Project::class => ProjectSimply::class,
        ]);
    }

    /**
     * Display project details.
     *
     * @param Project $project
     * @param Guard $guard
     * @param ServiceProjects $service
     * @return JsonResponse
     */
    public function show(Project $project, Guard $guard, Projects $service)
    {
        $project = $service->addDetails($project, $guard->user());

        if (Auth::user()->isAdmin() || Auth::user()->isOwner()) {
            return ApiResponse::transResponseOk(
                $project,
                200,
                [
                    Project::class => ProjectWithDetailsForAdmin::class,
                    User::class => TimeTrackingUserTransformer::class,
                    Activity::class => ActivitySummary::class,
                ]
            );
        }

        return ApiResponse::transResponseOk(
            $project,
            200,
            [
                Project::class => ProjectWithDetails::class,
                User::class => TimeTrackingUserTransformer::class,
                Activity::class => ActivitySummary::class,
            ]
        );
    }

    /**
     * Store a newly created project in db.
     *
     * @param ProjectStore $request
     * @param Guard $auth
     * @param Project $project
     * @param ServiceProjects $service
     * @return JsonResponse
     */
    public function store(ProjectStore $request, Guard $auth, Project $project, Projects $service)
    {
        if ($service->cantAddProjects($auth->user()->getSelectedCompanyId(), $project)) {
            return ApiResponse::responseError(ErrorCode::PACKAGE_LIMIT_REACHED, 409);
        }

        if ($service->checkTooManyUsers($auth->user()->getSelectedCompanyId(), count($request->users))) {
            return ApiResponse::responseError(ErrorCode::PACKAGE_TOO_MANY_USERS, 410);
        }

        $project = DB::transaction(function () use ($request, $auth) {
            $project = Project::create([
                'name' => $request->input('name'),
                'short_name' => $request->input('short_name'),
                'company_id' => $auth->user()->getSelectedCompanyId(),
                'created_tickets' => $request->input('first_number_of_tickets') - 1,
                'time_tracking_visible_for_clients' => $request->input('time_tracking_visible_for_clients'),
                'language' => $request->input('language', 'en'),
                'email_notification_enabled' => $request->input('email_notification_enabled', 0),
                'slack_notification_enabled' => $request->input('slack_notification_enabled', 0),
                'slack_webhook_url' => $request->input('slack_webhook_url'),
                'slack_channel' => $request->input('slack_channel'),
                'color' => $request->input('color'),
            ]);
            $project->users()->attach($request->mappedUsers());

            return $project;
        });

        return ApiResponse::responseOk($project->fresh(), 201);
    }

    /**
     * Update project.
     */
    public function update(ProjectUpdate $request, Project $project, Projects $service)
    {
        if ($service->checkTooManyUsers(auth()->user()->getSelectedCompanyId(), count($request->users))) {
            return ApiResponse::responseError(ErrorCode::PACKAGE_TOO_MANY_USERS, 410);
        }

        $service->updateProject($request, $project);

        return ApiResponse::responseOk($project->fresh());
    }

    /**
     * Close or open specified project.
     *
     * @param ProjectClose $request
     * @param Project $project
     *
     * @return Response
     */
    public function close(ProjectClose $request, Project $project)
    {
        if ($request->input('status') == 'close' && $project->isOpen()) {
            $project->closed_at = Carbon::now()->toDateTimeString();
        } elseif ($request->input('status') == 'open') {
            $project->closed_at = null;
        }
        $project->save();

        return ApiResponse::responseOk($project, 200);
    }

    /**
     * Soft remove the specified project.
     *
     * @param Project $project
     * @param Guard $auth
     * @param Company $company
     * @param CompanyModuleUpdater $updater
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(Project $project, Guard $auth, Company $company, CompanyModuleUpdater $updater)
    {
        $project->delete();

        //blockade company
        $updater->setCompany($company->find($auth->user()->getSelectedCompanyId()));
        $updater->updateBlockadedCompany(ModuleType::PROJECTS_MULTIPLE_PROJECTS);

        return ApiResponse::responseOk([], 204);
    }

    /**
     * Return very limited information about given project.
     *
     * @param Project $project
     * @param Guard $auth
     *
     * @return JsonResponse
     */
    public function basicInfo(Project $project, Guard $auth)
    {
        return ApiResponse::transResponseOk(
            $project,
            200,
            [Project::class => ProjectCompanyInfo::class]
        );
    }

    /**
     * Verify whether project exist by short name.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exist(Request $request)
    {
        Project::where('company_id', Auth::user()->getSelectedCompanyId())
            ->where('short_name', $request->input('short_name'))->firstOrFail();

        return ApiResponse::responseOk([], 200);
    }

    /**
     * @param ProjectClone $request
     * @param Guard $auth
     * @param Project $base_project
     * @param ServiceProjects $service
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function clone(
        ProjectClone $request,
        Guard $auth,
        Project $base_project,
        ServiceProjects $service
    ) {
        if ($service->cantAddProjects($auth->user()->getSelectedCompanyId(), $base_project)) {
            return ApiResponse::responseError(ErrorCode::PACKAGE_LIMIT_REACHED, 409);
        }

        $project = $service->cloneProject($base_project, $request);

        return ApiResponse::responseOk($project, 201);
    }
}
