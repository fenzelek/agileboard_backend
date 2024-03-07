<?php

namespace App\Modules\Notification\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Support\Arr;

class ReadNotificationRequest extends Request
{
    public function rules(): array
    {
        return [
            'notification_ids' => ['required', 'array'],
            'notification_ids.*' => ['string', 'max:255'],
        ];
    }

    /** @return string[] */
    public function getNotificationIds(): array
    {
        return $this->input('notification_ids', []);
    }
}
