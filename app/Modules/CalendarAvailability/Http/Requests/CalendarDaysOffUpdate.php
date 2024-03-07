<?php

namespace App\Modules\CalendarAvailability\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Db\User;
use App\Models\Other\UserCompanyStatus;
use App\Modules\CalendarAvailability\Contracts\DaysOffInterface;
use App\Modules\CalendarAvailability\Contracts\DaysOffProviderInterface;
use App\Modules\CalendarAvailability\Contracts\AvailabilityStore;
use App\Modules\CalendarAvailability\Contracts\AddDaysOffInterface;
use App\Modules\CalendarAvailability\Contracts\UpdateDaysOffInterface;
use App\Modules\CalendarAvailability\Http\Requests\Traits\CalendarAvailabilityStoreTrait;
use App\Modules\CalendarAvailability\Models\DayOffDTO;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Validation\Rule;

class CalendarDaysOffUpdate extends Request implements UpdateDaysOffInterface
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'user_id' => ['required', Rule::exists('users', 'id')],
            'added_days' => [ 'required', 'array'],
            'added_days.*.date' => [ 'required', 'date:Y-m-d'],
            'added_days.*.description' => [ 'required', 'string'],
            'deleted_days' => [ 'required', 'array'],
            'deleted_days.*' => [ 'required', 'date:Y-m-d'],
        ];
        //check process user
        $rules['user'] = Rule::exists('user_company', 'user_id')
            ->where('company_id', $this->getSelectedCompanyId())
            ->where('user_id', $this->input('user_id'))
            ->where('status', UserCompanyStatus::APPROVED);

        return $rules;
    }

    public function getSelectedCompanyId(): int
    {
        /**
         * @var User $user
         */
        $user = $this->user();
        return $user->getCompanyId();
    }

    public function getUserId(): int
    {
        return $this->input('user_id');
    }


    /**
     * @return DaysOffInterface[]
     */
    public function getAddedDays(): array
    {
        return array_map(fn(array $raw_day) => new DayOffDTO($raw_day['date'], $raw_day['description']),$this->input('added_days'));
    }

    /**
     * @return CarbonInterface[]
     */
    public function getDeletedDays(): array
    {
        return array_map(fn(string $day) => Carbon::parse($day),$this->input('deleted_days'));
    }
}
