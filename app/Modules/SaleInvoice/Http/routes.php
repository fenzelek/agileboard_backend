<?php

Route::group(['middleware' => 'api_authorized'], function () {
    Route::get('invoice-formats', 'InvoiceFormatController@index')->name('invoice-formats.index');
    Route::get('invoice-margin-procedures', 'InvoiceMarginProcedureController@index')->name('invoice-margin-procedures.index');
    Route::get('invoice-reverse-charges', 'InvoiceReverseChargeController@index')->name('invoice-reverse-charges.index');
    Route::get('invoice-correction-types', 'InvoiceCorrectionTypeController@index')->name('invoice-correction-types.index');
    Route::get('invoice-payments', 'InvoicePaymentsController@index')->name('invoice-payments.index');
    Route::delete('invoice-payments/{id}', 'InvoicePaymentsController@destroy')->name('invoice-payments.destroy');
    Route::post('invoice-payments', 'InvoicePaymentsController@store')->name('invoice-payments.store');
    Route::get('invoice-types', 'InvoiceTypeController@index');
    Route::get('invoice-filters', 'InvoiceFilterController@index');
    Route::get('companies/invoice-settings', 'InvoiceSettingsController@show')->name('invoice-settings.show');
    Route::put('companies/invoice-settings', 'InvoiceSettingsController@update')->name('invoice-settings.update');
    Route::get('invoices', 'InvoiceController@index')->name('invoices.index');
    Route::post('invoices', 'InvoiceController@store');
    Route::get('invoices/pdf', 'InvoiceController@indexPdf');
    Route::get('invoices/zip', 'InvoiceController@indexZip');
    Route::get('invoices/{id}', 'InvoiceController@show');
    Route::put('invoices/{id}', 'InvoiceController@update');
    Route::get('invoices/{id}/pdf', 'InvoiceController@pdf');
    Route::post('invoices/{id}/send', 'InvoiceController@send');
    Route::delete('invoices/{id}', 'InvoiceController@destroy');

    // JPK actions
    Route::get('invoices/jpk/fa', 'JpkController@index');
    Route::get('companies/jpk_details', 'JpkDetailsController@show');
    Route::put('companies/jpk_details', 'JpkDetailsController@update');
});
