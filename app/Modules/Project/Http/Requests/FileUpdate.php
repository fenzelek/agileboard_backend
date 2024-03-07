<?php

namespace App\Modules\Project\Http\Requests;

use App\Http\Requests\Request;

class FileUpdate extends FileStore
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $project_id = $this->route('project')->id;
        $company_id = auth()->user()->getSelectedCompanyId();

        $rules = $this->basicRules($company_id, $project_id);
        $rules['name'] = ['required', 'max:255'];

        return $rules;
    }
}
