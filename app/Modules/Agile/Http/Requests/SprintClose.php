<?php

namespace App\Modules\Agile\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Db\Sprint;
use Illuminate\Validation\Rule;

class SprintClose extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $sprint = $this->route('sprint');

        return [
            'sprint_id' => [
                'nullable',
                Rule::exists('sprints', 'id')
                    ->whereNot('status', Sprint::CLOSED)
                    ->where('id', '!=', $sprint->id)
                    ->where('project_id', $sprint->project_id),
            ],
        ];
    }
}
