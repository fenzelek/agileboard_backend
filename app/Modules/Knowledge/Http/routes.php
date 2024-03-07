<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Knowledge\Http\Controllers\KnowledgePageCommentController;

Route::group(['middleware' => 'api_authorized_in_project'], function () {
    Route::get('project/{project}/directories', 'KnowledgeDirectoryController@index')
    ->name('knowledge-directory.index');
    Route::post('project/{project}/directories', 'KnowledgeDirectoryController@store');
    Route::put('project/{project}/directories/{directory}', 'KnowledgeDirectoryController@update');
    Route::delete('project/{project}/directories/{directory}', 'KnowledgeDirectoryController@destroy');

    Route::get('project/{project}/pages', 'KnowledgePageController@index')
        ->name('knowledge-page.index');
    Route::post('project/{project}/pages', 'KnowledgePageController@store');
    Route::get('project/{project}/pages/{page}', 'KnowledgePageController@show');
    Route::put('project/{project}/pages/{page}', 'KnowledgePageController@update');
    Route::delete('project/{project}/pages/{page}', 'KnowledgePageController@destroy');

    Route::post('project/{project}/pages/{page}/comment', [KnowledgePageCommentController::class, 'store'])
        ->name('knowledge-page-comment.store');
    Route::put('project/{project}/pages/comments/{page_comment}', [KnowledgePageCommentController::class, 'update'])
        ->name('knowledge-page-comment.update');
    Route::delete('project/{project}/pages/comments/{page_comment}', [KnowledgePageCommentController::class, 'destroy'])
        ->name('knowledge-page-comment.destroy');
});
