<?php

Route::group(['middleware' => 'external_api_authorized'], function () {
    Route::post('receipts', 'ReceiptController@store')->middleware('log_errors');
    Route::post('online-sales', 'OnlineSaleController@store')->middleware('log_errors');
});

Route::group(['middleware' => 'api_authorized'], function () {
    Route::get('receipts', 'ReceiptController@index')->name('receipts.index');
    Route::get('receipts/pdf', 'ReceiptController@pdf')->name('receipts.pdf');
    Route::get('receipts/report', 'ReceiptController@pdfReport')->name('receipt.pdf-report');
    Route::get('receipts/{id}', 'ReceiptController@show');
    Route::get('online-sales', 'OnlineSaleController@index')->name('onlineSale.index');
    Route::get('online-sales/pdf', 'OnlineSaleController@pdf')->name('onlineSale.pdf');
    Route::get('online-sales/{id}', 'OnlineSaleController@show');

    Route::get('errors', 'ErrorLogController@index')->name('errors.index');
    Route::delete('errors', 'ErrorLogController@destroy');
});
