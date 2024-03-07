<?php

namespace App\Modules\Agile\Http\Requests;

use App\Http\Requests\Request;

class StatusIndex extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $return = [
            'tickets' => ['in:0,1'],
            'story_ids' => [
                'array',
            ],
            'story_ids.*' => ['integer'],
        ];

        if ($this->input('sprint_ids')) {
            $return['sprint_ids'] = [
                'array',
            ];
            $return['sprint_ids.*'] = ['integer'];
        }

        return $return;
    }
}
