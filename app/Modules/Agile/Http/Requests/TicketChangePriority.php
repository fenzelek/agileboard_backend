<?php

namespace App\Modules\Agile\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class TicketChangePriority extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $project = $this->route('project');

        $rules = [
            'before_ticket_id' => [
                'nullable',
                'integer',
                Rule::exists('tickets', 'id')
                    ->where('project_id', $project->id),
            ],

            'status_id' => [
                'nullable',
                Rule::exists('statuses', 'id')
                    ->where('project_id', $project->id),
            ],
        ];

        if ($this->input('sprint_id')) {
            $rules['sprint_id'] = [
                'nullable',
                Rule::exists('sprints', 'id')
                    ->where('project_id', $project->id),
            ];
        }

        return $rules;
    }
}
