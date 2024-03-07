<?php

namespace App\Modules\Agile\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class StatusUpdate extends Request
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
            'statuses' => [
                'required',
                'array',
            ],
            'statuses.*.delete' => ['required', 'in:0,1'],
        ];

        if (is_array($this->input('statuses'))) {
            foreach ($this->input('statuses') as $index => $status) {
                //delete
                if (isset($status['delete']) && $status['delete']) {
                    $return['statuses.' . $index . '.new_status'] = [
                        'required',
                        Rule::exists('statuses', 'id')->where('project_id', $project->id),
                    ];
                } else {
                    $return['statuses.' . $index . '.name'] = ['required', 'max:255'];
                }

                //id
                if (isset($status['id']) && $status['id']) {
                    $return['statuses.' . $index . '.id'] = [
                        'required',
                        Rule::exists('statuses', 'id')->where('project_id', $project->id),
                    ];
                }
            }
        }

        return $return;
    }
}
