<?php

namespace App\Modules\Project\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class AttachUser extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $selected_company_id = auth()->user()->getSelectedCompanyId();

        return [
            'user_id' => [
                'required',
                'numeric',
                Rule::exists('user_company', 'user_id')
                    ->where('company_id', $selected_company_id),
                Rule::unique('project_user', 'user_id')
                    ->where('project_id', $this->route('project')->id),
            ],
            'role_id' => [
                'required',
                'numeric',
                Rule::exists('company_role', 'role_id')
                    ->where('company_id', $selected_company_id),
            ],
        ];
    }
}
