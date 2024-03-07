<?php

namespace App\Modules\Integration\Http\Requests\TimeTracking;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class FetchProject extends Request
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            'integration_id' => [
                'required',
                'integer',
                Rule::exists('integrations', 'id')
                    ->where('company_id', $this->currentUser()->getSelectedCompanyId()),
            ],
        ];
    }
}
