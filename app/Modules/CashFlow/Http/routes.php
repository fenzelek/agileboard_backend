<?php

Route::group(['middleware' => 'api_authorized'], function () {
    Route::get('cash-flows', 'CashFlowController@index')->name('cash-flow.index');
    Route::post('cash-flows', 'CashFlowController@store')->name('cash-flow.store');
    Route::put('cash-flows/{id}', 'CashFlowController@update');
    Route::get('cash-flows/pdf', 'CashFlowController@pdf')->name('cash-flow.pdf');
    Route::get('cash-flows/{id}/pdf', 'CashFlowController@pdfItem')->name('cash-flow.pdfItem');
});
