<?php

namespace App\Modules\Integration\Http\Controllers;

use App\Exports\TimeTrackingActivityExport;
use App\Exports\TimeTrackingActivitySummaryExport;
use App\Filters\TimeTrackingActivityFilter;
use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\TicketShortEstimate;
use App\Http\Resources\TimeTracking\User as TimeTrackingUserTransformer;
use App\Http\Resources\TimeTracking\Activity as TimeTrackingActivityTransformer;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Modules\Integration\Exceptions\InvalidIdsException;
use App\Modules\Integration\Exceptions\InvalidManualActivityTimePeriod;
use App\Modules\Integration\Exceptions\InvalidManualIntegrationForCompany;
use App\Modules\Integration\Http\Requests\TimeTracking\ActivityBulkUpdate;
use App\Modules\Integration\Http\Requests\TimeTracking\DailyActivity;
use App\Modules\Integration\Http\Requests\TimeTracking\RemoveManualActivities;
use App\Modules\Integration\Http\Requests\TimeTracking\Report;
use App\Modules\Integration\Http\Requests\TimeTracking\StoreActivity;
use App\Modules\Integration\Http\Requests\TimeTracking\StoreOwnActivity;
use App\Modules\Integration\Services\ActivityReport;
use App\Modules\Integration\Services\Factories\ManualRemoveActivityManagerFactory;
use App\Modules\Integration\Services\Factories\ManualRemoveOwnActivityManagerFactory;
use App\Modules\Integration\Services\ManualActivityManager;
use App\Modules\Integration\Services\TimeTracker;
use App\Modules\Integration\Http\Resources\TimeSummary;
use App\Modules\Integration\Services\TimeTrackerActivities;
use App\Services\Paginator;
use Illuminate\Contracts\Auth\Guard;
use App\Modules\Integration\Http\Requests\TimeTracking\Activity as ActivityRequest;
use App\Models\Db\Integration\TimeTracking\User as TimeTrackingUser;
use DB;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Modules\Integration\Http\Resources\ActivityReport as ActivityReportResource;

class TimeTrackingActivityController extends Controller
{
    /**
     * Get list of time tracking activities.
     *
     * @param ActivityRequest $request
     * @param Guard $auth
     * @param Paginator $paginator
     * @param TimeTrackingActivityFilter $filter
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(
        ActivityRequest $request,
        Guard $auth,
        Paginator $paginator,
        TimeTrackingActivityFilter $filter
    ) {
        $all = $request->input('all');
        $min_utc_started_at = $request->input('min_utc_started_at');
        $max_utc_started_at = $request->input('max_utc_started_at');

        if ($all == true && $min_utc_started_at && $max_utc_started_at) {
            $response = Activity::filtered($filter)->get();
        } else {
            $response = $paginator->get(
                Activity::filtered($filter),
                'time-tracking-activity.index'
            );
        }

        return ApiResponse::transResponseOk($response, 200, [
            Activity::class => TimeTrackingActivityTransformer::class,
            TimeTrackingUser::class => TimeTrackingUserTransformer::class,
            Ticket::class => TicketShortEstimate::class,
        ]);
    }

    public function export(
        ActivityRequest $request,
        TimeTrackingActivityFilter $filter,
        TimeTrackerActivities $service
    ): BinaryFileResponse {
        $sum_ticket_times = json_decode($request->input('sum_ticket_times')) ?? false;
        $time_zone_offset = (int) $request->input('time_zone_offset') ?? 0;

        $activities = Activity::filtered($filter)->get();

        $export = $sum_ticket_times ?
            new TimeTrackingActivitySummaryExport(
                $service->getSummaryActivitiesForExport($activities)
            )
            : new TimeTrackingActivityExport(
                $service->getActivitiesForExport($activities),
                $time_zone_offset
            );

        return $export->download(Carbon::now()->timestamp . '.xlsx');
    }

    public function store(StoreActivity $request, ManualActivityManager $activity_manager, Guard $auth)
    {
        try {
            $activities = $activity_manager->addActivity($request, $auth->user());
        } catch (InvalidManualActivityTimePeriod $exception) {
            return ApiResponse::responseError(
                ErrorCode::INTEGRATION_INVALID_MANUAL_ACTIVITY_TIME_PERIOD,
                424
            );
        } catch (InvalidManualIntegrationForCompany $exception) {
            return ApiResponse::responseError(ErrorCode::INTEGRATION_INVALID, 403);
        }
        if (count($activities)) {
            return ApiResponse::transResponseOk([], 201);
        }

        return ApiResponse::transResponseOk([], 204);
    }

    public function storeOwnActivity(StoreOwnActivity $request, ManualActivityManager $activity_manager, Guard $auth)
    {
        try {
            $activities = $activity_manager->addActivity($request, $auth->user());
        } catch (InvalidManualActivityTimePeriod $exception) {
            return ApiResponse::responseError(
                ErrorCode::INTEGRATION_INVALID_MANUAL_ACTIVITY_TIME_PERIOD,
                424
            );
        } catch (InvalidManualIntegrationForCompany $exception) {
            return ApiResponse::responseError(ErrorCode::INTEGRATION_INVALID, 403);
        }
        if (count($activities)) {
            return ApiResponse::transResponseOk([], 201);
        }

        return ApiResponse::transResponseOk([], 204);
    }

    public function removeActivities(RemoveManualActivities $request, ManualRemoveActivityManagerFactory $activity_manager_factory)
    {
        try {
            $activity_manager = $activity_manager_factory->create();
            $response = $activity_manager->removeActivities($request);
        } catch (InvalidManualIntegrationForCompany $exception) {
            return ApiResponse::responseError(ErrorCode::INTEGRATION_INVALID, 403);
        } catch (InvalidIdsException $exception) {
            return ApiResponse::responseError(ErrorCode::INTEGRATION_REMOVE_ERROR, 405);
        }

        if (! $response) {
            return ApiResponse::transResponseOk([], 204);
        }

        return ApiResponse::responseError(ErrorCode::INTEGRATION_REMOVE_ERROR, 405);
    }

    public function removeOwnActivities(RemoveManualActivities $request, ManualRemoveOwnActivityManagerFactory $activity_manager_factory, Guard $auth)
    {
        try {
            /**
             * @var User $user
             */
            $user = $auth->user();
            $remove_activity_manager = $activity_manager_factory->create($user);
            $response = $remove_activity_manager->removeActivities($request);
        } catch (InvalidManualIntegrationForCompany $exception) {
            return ApiResponse::responseError(ErrorCode::INTEGRATION_INVALID, 403);
        } catch (InvalidIdsException $exception) {
            return ApiResponse::responseError(ErrorCode::INTEGRATION_REMOVE_ERROR, 405);
        }

        if (! $response) {
            return ApiResponse::transResponseOk([], 204);
        }

        return ApiResponse::responseError(ErrorCode::INTEGRATION_REMOVE_ERROR, 405);
    }

    /**
     * Get summary of selected activities.
     *
     * @param ActivityRequest $request
     * @param TimeTrackingActivityFilter $filtertime_tracker_frames
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function summary(
        ActivityRequest $request,
        TimeTrackingActivityFilter $filter
    ) {
        $data = [
            'sum_time' => Activity::filtered($filter)->sum('tracked'),
        ];

        return ApiResponse::transResponseOk($data);
    }

    public function dailySummary(
        DailyActivity $request,
        TimeTracker $time_tracker
    ) {
        $time_summary = $time_tracker->getTimeSummary($request);

        return (new TimeSummary($time_summary))->response()->setStatusCode(200);
    }

    /**
     * Bulk update time tracking activities.
     *
     * @param ActivityBulkUpdate $request
     * @param Guard $auth
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdate(ActivityBulkUpdate $request, Guard $auth)
    {
        DB::transaction(function () use ($request, $auth) {
            foreach ($request->input('activities') as $activity_data) {
                $activity = Activity::findOrFail($activity_data['id']);
                $data = array_only($activity_data, ['project_id', 'ticket_id', 'comment']);
                if ($auth->user()->isOwnerOrAdmin()) {
                    if ($activity_data['locked']) {
                        // if it was already locked - not change, if it wasn't set to current user
                        $locked_user_id = $activity->locked_user_id ?: $auth->user()->id;
                    } else {
                        // not locked - set to null
                        $locked_user_id = null;
                    }

                    $data['locked_user_id'] = $locked_user_id;
                }

                $activity->update($data);
            }
        });

        return ApiResponse::responseOk([]);
    }

    public function activityReport(Guard $auth, Report $request, ActivityReport $report): AnonymousResourceCollection
    {
        return ActivityReportResource::collection(
            $report->report($request->getDate(), $this->getAuthCompanyId($auth))
        );
    }

    private function getAuthCompanyId(Guard $auth): int
    {
        /** @var User $user */
        $user = $auth->user();
        return $user->getSelectedCompanyId();
    }
}
