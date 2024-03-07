<?php

namespace App\Modules\User\Http\Requests;

use App\Http\Requests\Request;

class UpdateUser extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'first_name' => [
                'required',
                'max:255',
            ],
            'last_name' => [
                'required',
                'max:255',
            ],
            'password' => [
                'confirmed',
                'min:6',
            ],
            'avatar' => ['image', 'mimes:jpg,jpeg,png', 'max:1024'],
            'remove_avatar' => ['required', 'in:0,1'],
        ];

        if ($this->input('password') && ! auth()->user()->isSystemAdmin()) {
            $rules['old_password'] = 'required';
        }

        return $rules;
    }
}
