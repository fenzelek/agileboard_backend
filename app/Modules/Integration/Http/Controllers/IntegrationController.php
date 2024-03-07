<?php

namespace App\Modules\Integration\Http\Controllers;

use App\Filters\IntegrationFilter;
use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\IntegrationProvider;
use App\Modules\Integration\Http\Requests\IntegrationCreate;
use App\Modules\Integration\Http\Requests\IntegrationCreateForProvider;
use App\Modules\Integration\Services\Factory;
use App\Modules\Integration\Services\TimeTracking\TrackTime;
use App\Services\Paginator;
use Illuminate\Contracts\Auth\Guard;
use App\Modules\Integration\Http\Requests\Integration as IntegrationRequest;
use DB;

class IntegrationController extends Controller
{
    /**
     * Get list of integrations.
     *
     * @param IntegrationRequest $request
     * @param Paginator $paginator
     * @param IntegrationFilter $filter
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(
        IntegrationRequest $request,
        Paginator $paginator,
        IntegrationFilter $filter
    ) {
        return ApiResponse::transResponseOk($paginator->get(
            Integration::filtered($filter),
            'integration.index'
        ), 200);
    }

    /**
     * Creates new integration for company.
     *
     * @param IntegrationCreate $request
     * @param Guard $auth
     * @param TrackTime $track_time
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(IntegrationCreate $request, Guard $auth, TrackTime $track_time)
    {
        $provider = IntegrationProvider::findOrFail($request->input('integration_provider_id'));

        /** @var \App\Modules\Integration\Services\Integration $integration */
        $integration = Factory::make($provider);

        // run specific integration validation
        /** @var IntegrationCreateForProvider $request */
        $request = app()->make($integration::getValidationClass());

        DB::beginTransaction();

        $integration_record = $integration::add(
            $auth->user()->selectedCompany(),
            $provider,
            $request->getSettingsFields()
        );

        // verify if given data is correct for time tracking
        if ($integration_record->provider->type == IntegrationProvider::TYPE_TIME_TRACKING) {
            if (! $track_time->verify($integration_record)) {
                DB::rollback();

                return ApiResponse::responseError(
                    ErrorCode::INTEGRATION_INVALID_TIME_TRACKING_DATA,
                    412
                );
            }
        }
        DB::commit();

        return ApiResponse::responseOk($integration_record->fresh(), 201);
    }
}
