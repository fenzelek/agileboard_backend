<?php

// for those routes we are going to use Company permissions
Route::group(['middleware' => 'api_authorized'], function () {
    Route::get(
        'projects',
        'ProjectController@index'
    )->name('projects.index');

    Route::get('projects/exist', 'ProjectController@exist');
    Route::post(
        'projects',
        'ProjectController@store'
    );
    Route::put(
        'projects/{project}/close',
        'ProjectController@close'
    );
    Route::get(
        'projects/{project}',
        'ProjectController@show'
    );
    Route::put(
        'projects/{project}',
        'ProjectController@update'
    );
    Route::delete(
        'projects/{project}',
        'ProjectController@destroy'
    )->name('projects.destroy');
    Route::post(
        'projects/{project}/clone',
        'ProjectController@clone'
    )->name('projects.clone');

    // Users in projects
    Route::get(
        'projects/{project}/users',
        'UserController@index'
    );
    Route::post(
        'projects/{project}/users',
        'UserController@store'
    );
    Route::delete(
        'projects/{project}/users/{user}',
        'UserController@destroy'
    );

    // Get info about company for given project
    Route::get('projects/{project}/basic-info', 'ProjectController@basicInfo');

    // Project permissions
    Route::get('projects/{project}/permissions', 'ProjectPermissionController@show')
        ->name('project-permissions.show');
    Route::put('projects/{project}/permissions', 'ProjectPermissionController@update')
        ->name('project-permissions.update');
});

// for those routes we are going to use Project permissions
Route::group(['middleware' => 'api_authorized_in_project'], function () {
    // Files in project
    Route::get('projects/files/types', 'FileController@types');
    Route::get('projects/{project}/files', 'FileController@index')->name('project-file.index');
    Route::post('projects/{project}/files', 'FileController@store');
    Route::delete('projects/{project}/files/{file}', 'FileController@destroy');
    Route::put('projects/{project}/files/{file}', 'FileController@update');
    Route::get('projects/{project}/files/{file}', 'FileController@show');
    Route::get('projects/{project}/files/{file}/download', 'FileController@download');
    // Stories in project
    Route::get('projects/{project}/stories', 'StoryController@index')->name('story.index');
    Route::post('projects/{project}/stories', 'StoryController@store');
    Route::put('projects/{project}/stories/{story}', 'StoryController@update');
    Route::get('projects/{project}/stories/{story}', 'StoryController@show');
    Route::delete('projects/{project}/stories/{story}', 'StoryController@destroy');
});
