<?php

namespace App\Modules\Agile\Http\Requests;

use App\Http\Requests\Request;

class SprintStoreUpdate extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $return = [
            'name' => ['required', 'max:255'],
            'planned_activation' => ['date_format:Y-m-d H:i:s'],
            'planned_closing' => ['date_format:Y-m-d H:i:s'],
        ];

        if ($this->input('planned_closing')) {
            $return['planned_activation'][] = 'before_or_equal:' . $this->input('planned_closing', '');
        }

        if ($this->input('planned_activation')) {
            $return['planned_closing'][] = 'after_or_equal:' . $this->input('planned_activation', '');
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function all($keys = null)
    {
        $data = parent::all();

        // make sure data will be trimmed before validation
        foreach ($data as $key => $val) {
            $data[$key] = is_string($val) ? trim($val) : '';
        }

        return $data;
    }
}
