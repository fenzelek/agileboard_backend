<?php

Route::group(['middleware' => 'api_authorized'], function () {
    Route::get('vat-rates', 'VatRateController@index')->name('vat-rates.index');
    Route::get('payment-methods', 'PaymentMethodsController@index')->name('payment-methods.index');
});
