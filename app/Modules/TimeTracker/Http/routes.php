<?php

use App\Modules\TimeTracker\Http\Controllers\ScreenshotController;
use App\Modules\TimeTracker\Http\Controllers\TimeTrackerController;

Route::group(['middleware' => 'api_authorized'], function () {
    Route::get('time-tracker/time-summary', [TimeTrackerController::class, 'getTimeSummary'])->name('time-tracker.time-summary');
    Route::post('time-tracker/add-screenshots', [TimeTrackerController::class, 'addScreenshots'])->name('time-tracker.add-screenshots');
    Route::get('time-tracker/screenshots', [ScreenshotController::class, 'index'])->name('time-tracker.screenshots.index');
    Route::get('time-tracker/screenshots/own', [ScreenshotController::class, 'indexOwn'])->name('time-tracker.screenshots.own');
});

Route::group(['middleware' => 'log_http'], function () {
    Route::post('time-tracker/add-frames', [TimeTrackerController::class, 'addFrames'])->name('time-tracker.add-frames')->middleware('api_authorized');
});
