<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Requests;

use App\Http\Requests\Request;
use App\Modules\Notification\Contracts\Request\INotificationRequest;

class NotificationRequest extends Request implements INotificationRequest
{
    public function rules(): array
    {
        return [
            'read' => ['boolean'],
            'page' => ['integer'],
            'per_page' => ['integer', 'lte:15'],
        ];
    }

    public function getReadFilter(): ?bool
    {
        return $this->input('read') ? (bool) $this->input('read') : null;
    }

    public function getPage(): int
    {
        return (int) $this->input('page', 1);
    }

    public function getPerPage(): int
    {
        return (int) $this->input('per_page', 15);
    }
}
