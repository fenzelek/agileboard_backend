<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Models\Db\User;
use App\Modules\Notification\Contracts\Request\INotificationRequest;
use Illuminate\Pagination\Paginator;

class NotificationQuery
{
    private NotificationFormatter $formatter;

    public function __construct(NotificationFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function userNotifications(User $user, int $company_id, INotificationRequest $request): Paginator
    {
        $paginated_notifications = $this->getUserNotifications($user, $company_id, $request);

        $paginated_notifications->setCollection(
            $this->formatter->format($paginated_notifications->collect())
        );

        return $paginated_notifications;
    }

    private function getUserNotifications(User $user, int $company_id, INotificationRequest $request): Paginator
    {
        $query = $user->notifications()->byCompany($company_id);

        if ($request->getReadFilter() === true) {
            $query->whereNotNull('read_at');
        } elseif ($request->getReadFilter() === false) {
            $query->whereNull('read_at');
        }

        /** @var Paginator */
        return $query->simplePaginate(
            $request->getPerPage(),
            ['*'],
            'page',
            $request->getPage()
        );
    }
}
