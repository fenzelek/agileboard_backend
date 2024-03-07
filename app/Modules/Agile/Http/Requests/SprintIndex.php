<?php

namespace App\Modules\Agile\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Db\Sprint;
use Illuminate\Validation\Rule;

class SprintIndex extends Request
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
            'status' => [
                'required',
                Rule::in([
                    Sprint::INACTIVE,
                    Sprint::ACTIVE,
                    Sprint::CLOSED,
                    'not-closed',
                ]),
            ],
            'story_ids' => [
                'array',
            ],
            'story_ids.*' => [
                'integer',
            ],
            'stats' => [
                Rule::in([
                    'no',
                    'min',
                    'all',
                ]),
            ],
            'with_tickets' => ['boolean'],
            'search' => 'max:500',
            'hidden' => ['in:0,1'],
            'with_backlog' => ['boolean'],
        ];

        if ($this->input('sprint_id')) {
            $return['sprint_id'] = [
                Rule::exists('sprints', 'id')
                    ->where('project_id', $project->id),
            ];
        }

        return $return;
    }
}
