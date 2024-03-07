<?php

namespace App\Modules\Project\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Db\Company;
use App\Models\Db\ModuleMod;
use App\Models\Other\ModuleType;
use Illuminate\Validation\Rule;

class FileStore extends Request
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
        $setting = Company::findOrFail($company_id)->appSettings(ModuleType::PROJECTS_FILE_SIZE);

        $rules = $this->basicRules($company_id, $project_id);
        $rules['file'] = ['required', 'file'];

        if ($setting != ModuleMod::UNLIMITED) {
            $rules['file'][] = 'max:' . ($setting * 1024);
        }

        return $rules;
    }

    protected function basicRules($company_id, $project_id)
    {
        return [
            'description' => ['max:255'],
            'temp' => ['in:0,1'],
            'roles' => ['array'],
            'roles.*' => [
                'integer',
                Rule::exists('company_role', 'role_id')
                    ->where('company_id', $company_id),
            ],
            'users' => ['array'],
            'users.*' => [
                'integer',
                Rule::exists('project_user', 'user_id')
                    ->where('project_id', $project_id),
            ],
            'pages' => ['array'],
            'pages.*' => [
                'integer',
                Rule::exists('knowledge_pages', 'id')
                    ->where('project_id', $project_id),
            ],
            'stories' => ['array'],
            'stories.*' => [
                'integer',
                Rule::exists('stories', 'id')
                    ->where('project_id', $project_id),
            ],
            'tickets' => ['array'],
            'tickets.*' => [
                'integer',
                Rule::exists('tickets', 'id')
                    ->where('project_id', $project_id),
            ],
        ];
    }
}
