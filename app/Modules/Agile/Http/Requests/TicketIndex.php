<?php

namespace App\Modules\Agile\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class TicketIndex extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $project = $this->route('project');

        $return = [
            'hidden' => ['in:0,1'],
            'story_id' => [
                Rule::exists('stories', 'id')
                    ->where('project_id', $project->id),
            ],
            'story_ids' => ['array'],
            'story_ids.*' => ['integer'],
            'search' => 'max:500',
            'limit' => ['nullable', 'integer'],
            'per_page' => 'numeric|gt:0',
            'page' => 'numeric|gt:0',
            'sort_by' => 'string|in:created_at,priority',
            'sort_type' => 'string|in:ASC,DESC',
        ];

        if ($this->input('sprint_id')) {
            $return['sprint_id'] = [
                Rule::exists('sprints', 'id')
                    ->where('project_id', $project->id),
            ];
        }

        return $return;
    }

    public function getPerPage(): int
    {
        return (int)$this->input('per_page', 10);
    }

    public function getPage(): int
    {
        return (int)$this->input('page', 1);
    }

    public function getLimit(): ?int
    {
        return $this->input('limit');
    }
}
