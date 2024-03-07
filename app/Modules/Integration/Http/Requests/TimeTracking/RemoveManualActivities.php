<?php

namespace App\Modules\Integration\Http\Requests\TimeTracking;

use App\Modules\Integration\Http\Requests\TimeTracking\Traits\RemoveManualActivitiesTrait;
use App\Modules\Integration\Http\Requests\TimeTracking\Traits\StoreActivityTrait;
use App\Modules\Integration\Services\Contracts\RemoveActivityProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RemoveManualActivities extends FormRequest implements RemoveActivityProvider
{
    use StoreActivityTrait;
    use RemoveManualActivitiesTrait;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return  [
            'activities' => [
                'required',
                'array',
            ],
            'activities.*' => Rule::exists('time_tracking_activities', 'id')->whereNull('deleted_at'),
        ];
    }
}
