<?php

Route::group(['middleware' => 'api_authorized'], function () {
    Route::resource('contractors', 'ContractorController', ['except' => ['create', 'edit']]);
});
