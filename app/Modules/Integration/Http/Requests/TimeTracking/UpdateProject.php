<?php

namespace App\Modules\Integration\Http\Requests\TimeTracking;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class UpdateProject extends Request
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')
                    ->where('company_id', $this->currentUser()->getSelectedCompanyId()),
            ],

        ];
    }
}
