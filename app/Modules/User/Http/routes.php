<?php

// api guest - throttling and verify if not logged
Route::group(['middleware' => 'api_guest'], function () {
    // log in
    Route::post('auth', 'AuthController@login')->name('auth.store');
    Route::post('auth/quick', 'AuthController@loginViaQuickToken');
    // password reset
    Route::post('password/reset', 'PasswordController@sendResetLinkEmail')
        ->name('password.reset.post');
    Route::put('password/reset', 'PasswordController@reset')->name('password.reset.put');
    // create account
    Route::post('users', 'UserController@store')->name('users.store');

    // activation
    Route::put('activation', 'ActivationController@activate')->name('activation.activate');
    Route::put('activation/resend', 'ActivationController@resend')->name('activation.resend');
});

// logout - throttling, auth (without refreshing token)
Route::group(['middleware' => 'api_logout'], function () {
    // log out
    Route::delete('auth', 'AuthController@logout')->name('auth.delete');
});

// standard authorized - throttling, auth, token refreshing, permission verification
Route::group(['middleware' => 'api_authorized'], function () {
    // roles
    Route::get('roles', 'RoleController@index')->name('roles.index');
    Route::get('roles/company', 'RoleController@company')->name('roles.company');
    // users
    Route::get('users', 'UserController@index')->name('users.index');
    Route::get('users/current', 'UserController@current')->name('users.current');
    Route::get('users/current/companies', 'UserController@companies')->name('users.current.companies');
    Route::put('users/{id}', 'UserController@update');
    Route::get('users/avatar/{avatar}', 'UserController@getAvatar');
});

Route::group(['middleware' => 'external_api_authorized'], function () {
    Route::post('auth/api', 'AuthController@apiToken');
});
