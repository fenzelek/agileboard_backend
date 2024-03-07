<?php

Route::group(['middleware' => 'api_authorized'], function () {
    Route::get(
        'users/{user}/availabilities/{day}',
        'CalendarAvailabilityController@show'
    );
    Route::post(
        'users/{user}/availabilities/{day}',
        'CalendarAvailabilityController@store'
    );
    Route::post(
        'users/availabilities/own/{day}',
        'CalendarAvailabilityController@storeOwn'
    );
    Route::post(
        'users/availabilities/report/',
        'CalendarAvailabilityController@report'
    )->name('availabilities.report.pdf');
    Route::get(
        'users/availabilities/',
        'CalendarAvailabilityController@index'
    );
    Route::get(
        'users/availabilities/export',
        'CalendarAvailabilityController@export'
    )->name('availabilities.export');
});

Route::group(['middleware' => 'external_api_authorized'], function () {
    Route::post(
        'users/availabilities/days-off',
        'CalendarDayOffController@add'
    )->name('calendar.days-off.add');

    Route::put(
        'users/availabilities/days-off',
        'CalendarDayOffController@update'
    )->name('calendar.days-off.update');

    Route::delete(
        'users/availabilities/days-off',
        'CalendarDayOffController@destroy'
    )->name('calendar.days-off.delete');
});
