<?php

namespace App\Modules\CalendarAvailability\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Models\Db\User;
use App\Modules\CalendarAvailability\Exceptions\InvalidTimePeriodAvailability;
use App\Modules\CalendarAvailability\Http\Requests\CalendarAvailabilityExportRequest;
use App\Modules\CalendarAvailability\Http\Requests\CalendarAvailabilityIndex;
use App\Modules\CalendarAvailability\Http\Requests\CalendarAvailabilityShow;
use App\Modules\CalendarAvailability\Http\Requests\CalendarAvailabilityStore;
use App\Modules\CalendarAvailability\Http\Requests\CalendarAvailabilityStoreOwn;
use App\Modules\CalendarAvailability\Http\Requests\CalendarAvailabilityReport;
use App\Modules\CalendarAvailability\Contracts\CalendarAvailability as CalendarService;
use App\Modules\CalendarAvailability\Services\AvailabilityFactory\StoreAvailabilityManagerFactory;
use App\Modules\CalendarAvailability\Services\AvailabilityFactory\StoreOwnAvailabilityManagerFactory;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\GetUserAvailability;
use App\Modules\CalendarAvailability\Services\CalendarAvailabilityExporter;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use PDF;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CalendarAvailabilityController extends Controller
{
    /**
     * Display list of calendar availabilities.
     *
     * @param CalendarAvailabilityIndex $request
     * @param CalendarService $service
     *
     * @return JsonResponse
     */
    public function index(
        CalendarAvailabilityIndex $request,
        CalendarService $service
    ) {
        $startDate = Carbon::parse($request->input('from'))->startOfWeek();
        $endDate = with(clone($startDate))
            ->addDays($request->input('limit', 10) - 1);

        $users = $service->find(
            $startDate,
            $endDate,
            $request->getSorts(),
            $request->getDepartment()
        );

        return ApiResponse::responseOk(
            $users,
            200,
            [
                'date_start' => $startDate->format('Y-m-d'),
                'date_end' => $endDate->format('Y-m-d'),
            ]
        );
    }

    public function export(
        CalendarAvailabilityExportRequest $request,
        CalendarAvailabilityExporter $exporter
    ): BinaryFileResponse {
        $export = $exporter->getExport(
            $request->getStartDate(),
            $request->getEndDate(),
            $request->getDepartment()
        );

        return Excel::download($export, Carbon::now()->timestamp . '.xlsx');
    }

    /**
     * Set user availability for given day. Removes any existing entries for
     * this user in this day.
     *
     * @param CalendarAvailabilityStoreOwn $request
     * @param StoreOwnAvailabilityManagerFactory $store_manager_factory
     * @param Guard $guard
     *
     * @return \Illuminate\Http\JsonResponse|Response|object
     */
    public function storeOwn(
        CalendarAvailabilityStoreOwn $request,
        StoreOwnAvailabilityManagerFactory $store_manager_factory,
        Guard $guard
    ) {
        /**
         * @var User $user
         */
        $user = $guard->user();
        $store_manager = $store_manager_factory->create($user);

        try {
            $response = $store_manager->storeAvailability($request, $user);
        } catch (InvalidTimePeriodAvailability $e) {
            return ApiResponse::responseError(
                ErrorCode::ERROR_TIME_PERIOD,
                422
            );
        }

        return ApiResponse::responseOk($response, 201);
    }

    /**
     * Set user availability for given day. Removes any existing entries for
     * this user in this day.
     *
     * @param CalendarAvailabilityStore $request
     * @param User $user
     * @param StoreAvailabilityManagerFactory $store_manager_factory
     *
     * @return \Illuminate\Http\JsonResponse|Response|object
     */
    public function store(
        CalendarAvailabilityStore $request,
        StoreAvailabilityManagerFactory $store_manager_factory,
        User $user
    ) {
        $store_manager = $store_manager_factory->create($user);

        try {
            $response = $store_manager->storeAvailability($request, $user);
        } catch (InvalidTimePeriodAvailability $e) {
            return ApiResponse::responseError(
                ErrorCode::ERROR_TIME_PERIOD,
                422
            );
        }

        return ApiResponse::responseOk($response, 201);
    }

    /**
     * Get calendar availability for selected user in selected day.
     *
     * @param CalendarAvailabilityShow $request
     * @param User $user
     * @param $day
     *
     * @return \Illuminate\Http\JsonResponse|Response
     * @internal param int $id
     */
    public function show(CalendarAvailabilityShow $request, User $user, $day, Guard $guard, GetUserAvailability $service)
    {
        $response = $service->get($user->id, $guard->user()->getSelectedCompanyId(), $day)->get();

        return ApiResponse::responseOk($response, 200);
    }

    /**
     * Generate PDFfile list of calendar availabilities.
     *
     * @param CalendarAvailabilityIndex $request
     * @param CalendarService $service
     */
    public function report(CalendarAvailabilityReport $request, CalendarService $service)
    {
        $date_from = $request->getFrom();
        $date_to = $request->getTo();
        $user_ids = $request->getUsersIds();

        $users_availabilities = $service->findByIds($date_from, $date_to, $user_ids);
        $report = $service->prepareDataToReport($users_availabilities);

        if ($request->inYear()) {
            $pdf = $this->createYearPDF($request, $report);
        } else {
            $pdf = $this->createMonthPDF($request, $report);
        }

        $file_name = "raport ({$date_from->format('Y-m-d')} - {$date_to->format('Y-m-d')}).pdf";

        return $pdf->stream($file_name);
    }

    /**
     * @param $request
     * @param array $report
     *
     * @return mixed
     */
    public function createMonthPDF($request, array $report)
    {
        $user = Auth::user();
        $date_from = $request->getFrom();
        $date_to = $request->getTo();

        return PDF::loadView('pdf/availability-report-month', [
            'int_month' => $date_from->month,
            'string_month' => $date_from->monthName,
            'date_from' => $date_from->format('Y-m-d'),
            'date_to' => $date_to->format('Y-m-d'),
            'report' => $report,
            'generate_time' => Carbon::now()->format('Y/m/d H:i:s'),
            'generate_user' => "{$user->first_name} {$user->last_name}",
            'is_admin' => $user->isOwnerOrAdminInCurrentCompany(),
            'user_id' => $user->id,
        ]);
    }

    /**
     * @param $request
     * @param array $report
     *
     * @return mixed
     */
    public function createYearPDF($request, array $report)
    {
        $user = Auth::user();
        $date_from = $request->getFrom();
        $date_to = $request->getTo();

        return PDF::loadView('pdf/availability-report-year', [
            'date_from' => $date_from->format('Y-m-d'),
            'date_to' => $date_to->format('Y-m-d'),
            'report' => $report,
            'generate_time' => Carbon::now()->format('Y/m/d H:i:s'),
            'generate_user' => "{$user->first_name} {$user->last_name}",
            'is_admin' => $user->isOwnerOrAdminInCurrentCompany(),
            'user_id' => $user->id,
        ]);
    }
}
