<?php

namespace App\Modules\Project\Http\Controllers;

use App\Filters\StoryFilter;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Db\Project;
use App\Models\Db\Story;
use App\Modules\Project\Http\Requests\StoryStore as RequestsStoryStore;
use App\Modules\Project\Http\Requests\StoryUpdate as RequestsStoryUpdate;
use App\Modules\Project\Services\Story as ServiceStory;
use App\Services\Paginator;

class StoryController extends Controller
{
    /**
     * Get list of stories for given project.
     *
     * @param Project $project
     * @param Paginator $paginator
     * @param StoryFilter $filter
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Project $project, Paginator $paginator, StoryFilter $filter)
    {
        $stories = $paginator->get(
            Story::where('project_id', $project->id)->filtered($filter),
            'story.index',
            ['project' => $project->id]
        );

        return ApiResponse::responseOk($stories);
    }

    /**
     * Create new story.
     *
     * @param Project $project
     * @param RequestsStoryStore $request
     * @param ServiceStory $service
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Project $project, RequestsStoryStore $request, ServiceStory $service)
    {
        $story = $service->store($project, $request);

        return ApiResponse::responseOk($story, 201);
    }

    /**
     * Update story.
     *
     * @param Project $project
     * @param Story $story
     * @param RequestsStoryUpdate $request
     * @param ServiceStory $service
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(
        Project $project,
        Story $story,
        RequestsStoryUpdate $request,
        ServiceStory $service
    ) {
        $story = $service->update($story, $request);

        return ApiResponse::responseOk($story, 200);
    }

    /**
     * Show given project.
     *
     * @param Project $project
     * @param Story $story
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Project $project, Story $story)
    {
        return ApiResponse::responseOk($story, 200);
    }

    /**
     * Soft deletes story.
     *
     * @param Project $project
     * @param Story $story
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Project $project, Story $story)
    {
        $story->delete();

        return ApiResponse::responseOk([], 204);
    }
}
