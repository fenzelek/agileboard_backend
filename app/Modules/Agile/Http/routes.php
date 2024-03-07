<?php

Route::group(['middleware' => 'api_authorized'], function () {
    Route::get('ticket-types', 'TicketTypeController@index');
    Route::get('ticket-realization', 'TicketRealizationController@index');
    Route::get('reports/tickets/daily', 'ReportController@daily')->name('reports.daily');
    Route::get('dashboard', 'DashboardController@index');
});

Route::group(['middleware' => 'api_authorized_in_project'], function () {
    Route::get('projects/{project}/sprints', 'SprintController@index');
    Route::post('projects/{project}/sprints', 'SprintController@store');
    Route::put('projects/{project}/sprints/change-priority', 'SprintController@changePriority');
    Route::get('projects/{project}/sprints/{sprint}/export', 'SprintController@export')->name('projects.sprint.export');
    Route::put('projects/{project}/sprints/{sprint}', 'SprintController@update');
    Route::put('projects/{project}/sprints/{sprint}/activate', 'SprintController@activate');
    Route::put('projects/{project}/sprints/{sprint}/pause', 'SprintController@pause');
    Route::put('projects/{project}/sprints/{sprint}/resume', 'SprintController@resume');
    Route::put('projects/{project}/sprints/{sprint}/lock', 'SprintController@lock');
    Route::put('projects/{project}/sprints/{sprint}/unlock', 'SprintController@unlock');
    Route::put('projects/{project}/sprints/{sprint}/close', 'SprintController@close');
    Route::post('projects/{project}/sprints/{sprint}/clone', 'SprintController@clone')->name('sprints.clone');
    Route::delete('projects/{project}/sprints/{sprint}', 'SprintController@destroy');

    Route::get('projects/{project}/tickets', 'TicketController@index')->name('tickets.index');
    Route::post('projects/{project}/tickets', 'TicketController@store');
    Route::get('projects/{project}/tickets/{ticket}', 'TicketController@show');
    Route::put('projects/{project}/tickets/{ticket}', 'TicketController@update');
    Route::put('projects/{project}/tickets/{ticket}/show', 'TicketController@setFlagToShow');
    Route::put('projects/{project}/tickets/{ticket}/hide', 'TicketController@setFlagToHide');
    Route::delete('projects/{project}/tickets/{ticket}', 'TicketController@destroy');
    Route::put('projects/{project}/tickets/{ticket}/change-priority', 'TicketController@changePriority');
    Route::get('projects/{project}/tickets/{ticket}/history', 'TicketController@history')->name('tickets.history');

    Route::post('projects/{project}/comments', 'TicketCommentController@store');
    Route::put('projects/{project}/comments/{ticket_comment}', 'TicketCommentController@update');
    Route::delete('projects/{project}/comments/{ticket_comment}', 'TicketCommentController@destroy');

    Route::get('projects/{project}/statuses', 'StatusController@index');
    Route::post('projects/{project}/statuses', 'StatusController@store');
    Route::put('projects/{project}/statuses', 'StatusController@update');
});
