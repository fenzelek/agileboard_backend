<?php

use App\Modules\Integration\Http\Controllers\TimeTrackingActivityController;

Route::group(['middleware' => 'api_authorized'], function () {
    Route::get('integrations/providers', 'IntegrationProviderController@index');
    Route::post('integrations', 'IntegrationController@store');
    Route::get('integrations', 'IntegrationController@index')->name('integration.index');

    Route::get('integrations/time_tracking/activities', 'TimeTrackingActivityController@index')->name('time-tracking-activity.index');
    Route::get('integrations/time_tracking/activities/export', 'TimeTrackingActivityController@export')->name('time-tracking-activity.export');
    Route::post('integrations/time_tracking/activities', 'TimeTrackingActivityController@store')->name('time-tracking-activity.store');
    Route::post('integrations/time_tracking/activities/own', 'TimeTrackingActivityController@storeOwnActivity')->name('time-tracking-activity.store-own-activity');
    Route::delete('integrations/time_tracking/activities/own', 'TimeTrackingActivityController@removeOwnActivities')->name('time-tracking-activity.remove-own-activities');
    Route::delete('integrations/time_tracking/activities', 'TimeTrackingActivityController@removeActivities')->name('time-tracking-activity.remove-activities');
    Route::get('integrations/time_tracking/activities/summary', 'TimeTrackingActivityController@summary');
    Route::get('integrations/time_tracking/activities/daily-summary', 'TimeTrackingActivityController@dailySummary');
    Route::put('integrations/time_tracking/activities', 'TimeTrackingActivityController@bulkUpdate');
    Route::get('integrations/time_tracking/projects', 'TimeTrackingProjectController@index')->name('time-tracking-project.index');
    Route::put('integrations/time_tracking/projects/fetch', 'TimeTrackingProjectController@fetch');
    Route::put('integrations/time_tracking/projects/{time_tracking_project}', 'TimeTrackingProjectController@update');

    Route::get('integrations/time_tracking/users', 'TimeTrackingUserController@index')->name('time-tracking-user.index');

    Route::get('integrations/time_tracking/report', [TimeTrackingActivityController::class, 'activityReport'])
        ->name('time-tracking.report');
});
