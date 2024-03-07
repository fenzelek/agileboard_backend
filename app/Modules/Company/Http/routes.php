<?php

Route::group(['middleware' => 'api_guest'], function () {
    Route::put('companies/invitations/accept', 'InvitationController@accept');
    Route::put('companies/invitations/reject', 'InvitationController@reject');

    Route::post('companies/payments/notification/{currency}', 'PaymentController@getNotification');
});

Route::group(['middleware' => 'api_authorized'], function () {
    Route::get('companies', 'CompanyController@index');
    Route::post('companies', 'CompanyController@store');
    Route::put('companies', 'CompanyController@update');
    Route::put('companies/settings', 'CompanyController@updateSettings');
    Route::get('companies/current', 'CompanyController@showCurrent');
    Route::get('companies/get-logotype', 'CompanyController@getLogotype');
    Route::get('companies/get-logotype/{id}', 'CompanyController@getLogotypeSelectedCompany');
    Route::get('companies/get-gus-data', 'CompanyController@getGusData');
    Route::get('companies/country-vatin-prefixes', 'CompanyController@indexCountryVatinPrefixes');

    // users in company
    Route::get('companies/current/users', 'UserController@index');
    Route::delete('companies/current/users/{id}', 'UserController@destroy');
    Route::put('companies/current/users', 'UserController@update');

    // company services
    Route::get('companies/services', 'CompanyServiceController@index')->name('company-service.index');
    Route::get('companies/services/{id}', 'CompanyServiceController@show');
    Route::post('companies/services', 'CompanyServiceController@store');
    Route::put('companies/services/{id}', 'CompanyServiceController@update');

    // company service units
    Route::get('companies/service-units', 'CompanyServiceUnitController@index');

    // Vat release reasons
    Route::get('vat-release-reasons', 'VatReleaseReasonController@index');

    Route::get('companies', 'CompanyController@index');
    Route::put('companies', 'CompanyController@update');
    Route::put('companies/default-payment-method', 'CompanyController@updatePaymentMethod');

    // invitations
    Route::post('companies/{id}/invitations', 'InvitationController@store');
    Route::get('users/current/invitations', 'InvitationController@currentIndex');

    // tokens
    Route::post('companies/tokens', 'TokenController@store');
    Route::get('companies/tokens', 'TokenController@index')->name('token.index');
    Route::delete('companies/tokens/{id}', 'TokenController@destroy');

    // tax offices
    Route::get('tax-offices', 'TaxOfficeController@index');

    //packages
    Route::get('packages', 'PackageController@index');
    Route::get('packages/current', 'PackageController@current');
    Route::get('packages/{package}', 'PackageController@show');
    Route::post('packages', 'PackageController@store');

    Route::get('modules/current', 'ModuleController@current');
    Route::get('modules/available', 'ModuleController@available');
    Route::get('modules/limits', 'ModuleController@limits');
    Route::post('modules', 'ModuleController@store');
    Route::delete('modules/{id}', 'ModuleController@destroy');

    //payments
    Route::get('companies/payments', 'PaymentController@index')->name('payments.index');
    Route::post('companies/payments/again/{transaction}', 'PaymentController@payAgain');
    Route::get('companies/payments/cards', 'PaymentController@cardList');
    Route::delete('companies/payments/subscription/{subscription}', 'PaymentController@cancelSubscription');
    Route::get('companies/payments/{payment}', 'PaymentController@show');
    Route::post('companies/payments/{payment}', 'PaymentController@confirmBuy');
    Route::delete('companies/payments/{payment}', 'PaymentController@cancelPayment');

    // clipboard
    Route::get('clipboard', 'ClipboardController@index')->name('clipboard.index');
    Route::get('clipboard/{id}', 'ClipboardController@download');
});
