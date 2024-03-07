<?php

namespace App\Modules\CalendarAvailability\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Models\Db\User;
use App\Modules\CalendarAvailability\Contracts\AddDaysOffInterface;
use App\Modules\CalendarAvailability\Exceptions\InvalidTimePeriodAvailability;
use App\Modules\CalendarAvailability\Http\Requests\CalendarAvailabilityAddDaysOff;
use App\Modules\CalendarAvailability\Http\Requests\CalendarAvailabilityIndex;
use App\Modules\CalendarAvailability\Http\Requests\CalendarAvailabilityShow;
use App\Modules\CalendarAvailability\Http\Requests\CalendarAvailabilityStore;
use App\Modules\CalendarAvailability\Http\Requests\CalendarAvailabilityStoreOwn;
use App\Modules\CalendarAvailability\Http\Requests\CalendarAvailabilityReport;
use App\Modules\CalendarAvailability\Contracts\CalendarAvailability as CalendarService;
use App\Modules\CalendarAvailability\Http\Requests\CalendarDaysOffDelete;
use App\Modules\CalendarAvailability\Http\Requests\CalendarDaysOffUpdate;
use App\Modules\CalendarAvailability\Models\AddDaysOffAdapter;
use App\Modules\CalendarAvailability\Models\DeleteDaysOffAdapter;
use App\Modules\CalendarAvailability\Models\ProcessListAvailabilityDTO;
use App\Modules\CalendarAvailability\Models\UserAvailabilityAdapter;
use App\Modules\CalendarAvailability\Services\AvailabilityFactory\StoreAvailabilityManagerFactory;
use App\Modules\CalendarAvailability\Services\AvailabilityFactory\StoreOwnAvailabilityManagerFactory;
use App\Modules\CalendarAvailability\Services\AvailabilityFactory\DaysOffManagerFactory;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\GetUserAvailability;
use App\Modules\CalendarAvailability\Services\DaysOffRemover;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Response;
use PDF;
use Illuminate\Support\Facades\Auth;

class CalendarDayOffController extends Controller
{
    public function add(CalendarAvailabilityAddDaysOff $request,
        DaysOffManagerFactory $store_manager_factory,
        User $user
    ) {
        try {
            $added = $this->addDaysOff($user, $request, $store_manager_factory);
        } catch (InvalidTimePeriodAvailability $e) {
            return ApiResponse::responseError(
                ErrorCode::ERROR_TIME_PERIOD,
                422
            );
        }

        return ApiResponse::responseOk(compact('added'), 201);
    }

    public function update(CalendarDaysOffUpdate $request,
        DaysOffManagerFactory $store_manager_factory,
        User $user,
        DaysOffRemover $days_off_remover){

        try {
            $availability = new AddDaysOffAdapter($request);
            $added = $this->addDaysOff($user, $availability, $store_manager_factory);
        } catch (InvalidTimePeriodAvailability $e) {
            return ApiResponse::responseError(
                ErrorCode::ERROR_TIME_PERIOD,
                422
            );
        }

        $delete_days_off = new DeleteDaysOffAdapter($request);
        $deleted = $days_off_remover->delete($delete_days_off);
        return ApiResponse::responseOk(compact('added', 'deleted'), 200);
    }

    public function destroy(CalendarDaysOffDelete $request, DaysOffRemover $days_off_remover){
        $deleted = $days_off_remover->delete($request);
            return ApiResponse::responseOk(compact('deleted'), 200);
    }

    /**
     * @param  User  $user
     * @param  CalendarDaysOffUpdate  $request
     * @param  DaysOffManagerFactory  $store_manager_factory
     * @return bool
     * @throws InvalidTimePeriodAvailability
     */
    private function addDaysOff(
        User $user,
        AddDaysOffInterface $request,
        DaysOffManagerFactory $store_manager_factory
    ): bool {
        $user = $user->newQuery()->find($request->getUserId());
        $store_manager = $store_manager_factory->create($user);
        $added = $store_manager->storeAvailability($request);
        return $added;
    }

}
