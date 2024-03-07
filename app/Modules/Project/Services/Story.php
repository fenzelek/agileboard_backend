<?php

namespace App\Modules\Project\Services;

use App\Models\Db\Project;
use App\Models\Db\Story as ModelStory;
use Illuminate\Http\Request;
use Illuminate\Database\Connection;

class Story
{
    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var ModelStory
     */
    protected $model_story;

    /**
     * Story constructor.
     *
     * @param Connection $db
     * @param ModelStory $model_story
     */
    public function __construct(Connection $db, ModelStory $model_story)
    {
        $this->db = $db;
        $this->model_story = $model_story;
    }

    /**
     * Create new story.
     *
     * @param Project $project
     * @param Request $request
     *
     * @return ModelStory
     */
    public function store(Project $project, Request $request)
    {
        $current_priority = $this->model_story->where('project_id', $project->id)->max('priority');

        return $this->db->transaction(function () use ($project, $request, $current_priority) {
            /** @var ModelStory $story */
            $story = $project->stories()->create([
                'name' => $request->input('name'),
                'color' => $request->input('color', ''),
                'priority' => $current_priority ? $current_priority + 1 : 1,
            ]);

            $story->files()->attach($request->input('files'));
            $story->tickets()->attach($request->input('tickets'));
            $story->pages()->attach($request->input('knowledge_pages'));

            return $story;
        });
    }

    /**
     * Update story.
     *
     * @param ModelStory $story
     * @param Request $request
     *
     * @return ModelStory
     */
    public function update(ModelStory $story, Request $request)
    {
        return $this->db->transaction(function () use ($story, $request) {
            $story->update($request->only('name', 'priority', 'color'));

            if ($request->input('files', null) !== null) {
                $story->files()->sync((array) $request->input('files'));
            }
            if ($request->input('tickets', null) !== null) {
                $story->tickets()->sync((array) $request->input('tickets'));
            }
            if ($request->input('knowledge_pages', null) !== null) {
                $story->pages()->sync((array) $request->input('knowledge_pages'));
            }

            return $story;
        });
    }
}
