<?php

namespace App\Modules\TimeTracker\Http\Requests;

use App\Http\Requests\Request;
use App\Modules\TimeTracker\Http\Requests\Contracts\GetScreenshotsQueryData;
use Illuminate\Validation\Rule;

class GetScreenshotsQuery extends GetOwnScreenshotsRequest implements GetScreenshotsQueryData
{
    public function rules(): array
    {
        return parent::rules() + [
            'user_id' => ['required', Rule::exists('users', 'id')],
        ];
    }

    public function getUserId(): int
    {
        return $this->input('user_id');
    }
}
