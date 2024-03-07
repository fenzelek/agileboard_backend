<?php

namespace App\Modules\Project\Http\Requests;

use App\Http\Requests\Request;

class ProjectUser extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'search' => ['nullable', 'string'],
            'user_id' => ['nullable','integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function all($keys = null)
    {
        $data = parent::all();

        // if special value 'current' is sent, we will use current user id
        if (array_get($data, 'user_id') == 'current') {
            $data['user_id'] = $this->container['auth']->user()->id;
        }

        return $data;
    }
}
