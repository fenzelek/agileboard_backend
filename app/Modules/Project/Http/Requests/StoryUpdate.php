<?php

namespace App\Modules\Project\Http\Requests;

use Illuminate\Validation\Rule;

class StoryUpdate extends StoryStore
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $project_id = $this->route('project')->id;
        $story_id = $this->route('story')->id;

        $rules = [
            'name' => [
                'required',
                'max:255',
                Rule::unique('stories')->ignore($story_id)
                    ->whereNull('deleted_at')
                    ->where('project_id', $project_id),
            ],
            'priority' => [
                'required',
                'integer',
                Rule::unique('stories')->ignore($story_id)
                    ->whereNull('deleted_at')
                    ->where('project_id', $project_id),
            ],
            'color' => [
                'size:7',
            ],
        ];

        return array_merge($rules, $this->commonRules($project_id));
    }
}
