<?php

namespace App\Modules\Agile\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Db\Sprint;
use Illuminate\Validation\Rule;

class SprintChangePriority extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $project = $this->route('project');

        return [
            'sprints' => [
                'required',
                'array',
            ],
            'sprints.*' => [
                Rule::exists('sprints', 'id')
                    ->whereNot('status', Sprint::CLOSED)
                    ->where('project_id', $project->id),
            ],
        ];
    }
}
