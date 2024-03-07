<?php

namespace App\Modules\TimeTracker\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Modules\TimeTracker\Http\Requests\AddFrames;
use App\Modules\TimeTracker\Http\Requests\AddScreenshots;
use App\Modules\TimeTracker\Http\Requests\GetTimeSummary;
use App\Modules\TimeTracker\Http\Resources\AddFramesResource;
use App\Modules\TimeTracker\Http\Resources\TimeSummary;
use App\Modules\TimeTracker\Services\ScreenDBSaver;
use App\Modules\TimeTracker\Services\TimeTracker as TimeTrackerService;
use App\Modules\TimeTracker\Services\StorageScreenshot;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class TimeTrackerController extends Controller
{
    public function getTimeSummary(GetTimeSummary $request, TimeTrackerService $service)
    {
        $time_summary = $service->getTimeSummary(Carbon::now(), $request->getTimeZoneOffset());

        return (new TimeSummary($time_summary))->response()->setStatusCode(200);
    }

    public function addFrames(AddFrames $request, TimeTrackerService $service)
    {
        $process_result = $service->processFrames($request);
        $time_summary = $service->getTimeSummary(Carbon::now());

        return (new AddFramesResource($time_summary, $process_result))->response()
            ->setStatusCode(201);
    }

    /**
     * @throws \Exception
     */
    public function addScreenshots(AddScreenshots $screen_files_provider, StorageScreenshot $storage_service, ScreenDBSaver $screen_service)
    {
        /**
         * @param $screen_files_provider
         *
         * @return bool
         */
        $screen_response = $storage_service->addScreenshot($screen_files_provider, $screen_service);

        if ($screen_response) {
            return response('', 200);
        }

        return ApiResponse::responseError(
            ErrorCode::ERROR_SAVE_PICTURE,
            Response::HTTP_FAILED_DEPENDENCY
        );
    }
}
