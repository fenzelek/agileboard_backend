<?php

namespace App\Modules\Project\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class DetachUser extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'user_id' => [
                'required',
                'numeric',
                Rule::exists('project_user', 'user_id')
                    ->where('project_id', $this->route('project')->id),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function all($keys = null)
    {
        $data = parent::all();
        // add extra data that should be validated
        $data['user_id'] = ($user = $this->route('user')) ? $user->id : null;

        return $data;
    }
}
