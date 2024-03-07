<?php

namespace App\Modules\Project\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class StoryStore extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $project_id = $this->route('project')->id;

        $rules = [
            'name' => [
                'required',
                'max:255',
                Rule::unique('stories')->where('project_id', $project_id),
            ],
            'color' => [
                'size:7',
            ],
        ];

        return array_merge($rules, $this->commonRules($project_id));
    }

    /**
     * Common rules for store and update.
     *
     * @param int $project_id
     *
     * @return array
     */
    protected function commonRules($project_id)
    {
        $return = [];

        if ($this->input('files', null) !== null) {
            $return['files'] = ['present', 'array'];
            $return['files.*'] = [
                'nullable',
                'integer',
                Rule::exists('files', 'id')
                    ->where('project_id', $project_id),
                'distinct',
            ];
        }

        if ($this->input('tickets', null) !== null) {
            $return['tickets'] = ['present', 'array'];
            $return['tickets.*'] = [
                'nullable',
                'integer',
                Rule::exists('tickets', 'id')
                    ->where('project_id', $project_id),
                'distinct',
            ];
        }

        if ($this->input('knowledge_pages', null) !== null) {
            $return['knowledge_pages'] = ['present', 'array'];
            $return['knowledge_pages.*'] = [
                'nullable',
                'integer',
                Rule::exists('knowledge_pages', 'id')
                    ->where('project_id', $project_id),
                'distinct',
            ];
        }

        return $return;
    }
}
