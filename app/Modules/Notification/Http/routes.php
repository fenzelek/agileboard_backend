<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Modules\Notification\Http\Controllers\NotificationController;

Route::group(['middleware' => 'api_user'], function () {
    Route::get('user/notifications', [NotificationController::class, 'userNotifications'])
        ->name('user.notifications');
    Route::get('user/notifications/unread-count', [NotificationController::class, 'unreadCount'])
        ->name('user.notifications.unread-count');
    Route::put('user/notifications/read', [NotificationController::class, 'read'])
        ->name('user.notifications.read');
    Route::put('user/notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('user.notifications.read-all');
});
