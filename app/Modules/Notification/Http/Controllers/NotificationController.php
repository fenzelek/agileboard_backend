<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Db\User;
use App\Modules\Notification\Http\Requests\NotificationRequest;
use App\Modules\Notification\Http\Resources\NotificationCollection;
use App\Modules\Notification\Http\Requests\ReadNotificationRequest;
use App\Modules\Notification\Services\NotificationQuery;
use App\Modules\Notification\Services\NotificationReader;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    private NotificationReader $notification_reader;
    private NotificationQuery $notification_query;

    public function __construct(NotificationReader $notification_reader, NotificationQuery $notification_query)
    {
        $this->notification_reader = $notification_reader;
        $this->notification_query = $notification_query;
    }

    public function userNotifications(NotificationRequest $request, Guard $auth): JsonResponse
    {
        $user = $this->user($auth);

        $resource = $this->notification_query->userNotifications(
            $user,
            $user->getSelectedCompanyId(),
            $request
        );

        return new JsonResponse(
            new NotificationCollection($resource)
        );
    }

    public function unreadCount(Guard $auth): JsonResponse
    {
        $user = $this->user($auth);

        return new JsonResponse([
            'count' => $user->unreadNotifications()
                ->byCompany($user->getSelectedCompanyId())
                ->count(),
        ]);
    }

    public function read(ReadNotificationRequest $request, Guard $auth): JsonResponse
    {
        $user = $this->user($auth);

        $result = $this->notification_reader->read(
            $request->getNotificationIds(),
            $user->id,
            $user->getSelectedCompanyId()
        );

        if ($result->success()) {
            return new JsonResponse([]);
        }

        return new JsonResponse([
            'message' => $result->getFailReason(),
            'errors' => $result->getErrors(),
        ], 400);
    }

    public function readAll(Guard $auth): JsonResponse
    {
        $user = $this->user($auth);

        $this->notification_reader->readAll(
            $user->id,
            $user->getSelectedCompanyId()
        );

        return new JsonResponse([]);
    }

    private function user(Guard $auth): User
    {
        /** @var User */
        return $auth->user();
    }
}
