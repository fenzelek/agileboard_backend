<?php

Route::group(['middleware' => 'api_authorized'], function () {
    Route::get('workload', 'WorkloadController@index')->name('workload.index');
});
