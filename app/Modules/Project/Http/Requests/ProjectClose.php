<?php

namespace App\Modules\Project\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class ProjectClose extends Request
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
            'project_id' => [
                'required',
                Rule::exists('projects', 'id')
                    ->where('company_id', $selected_company_id),
            ],
            'status' => [
                'required',
                'string',
                'in:close,open',
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
        $data['project_id'] = ($project = $this->route('project')) ? $project->id : null;

        return $data;
    }
}
