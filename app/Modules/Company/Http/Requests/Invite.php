<?php

namespace App\Modules\Company\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Other\ModuleType;
use App\Models\Other\RoleType;
use App\Models\Db\Role;
use App\Models\Other\UserCompanyStatus;
use App\Rules\Blacklist;
use Illuminate\Validation\Rule;

class Invite extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $userId = auth()->user()->id;
        $selected_company_id = $this->input('selected_company_id');
        $company_roles = Role::whereHas('companies', function ($q) use ($selected_company_id) {
            $q->where('id', $selected_company_id);
        })->where('name', '<>', RoleType::OWNER)->pluck('id')->all();

        $rules = [
            'email' => ['required', 'email', 'max:255', new Blacklist()],
            'first_name' => ['max:255'],
            'last_name' => ['max:255'],
            'language' => ['in:en,pl'],
            'role_id' => [
                'required',
                Rule::in($company_roles),
            ],
            'company_id' => [
                'required',
                'exists:user_company,company_id,user_id,' . $userId . ',status,' .
                UserCompanyStatus::APPROVED,
                'in:' . $selected_company_id,
                // @todo verify why below was not working
                // 'same:selected_company_id',
            ],

        ];
        // check invitations settings
        if ($this->canSendInvitation()) {
            $rules['url'] = ['required'];
        } else {
            $rules['password'] = ['required', 'confirmed'];
            $rules['email'][] = Rule::unique('users', 'email');
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function all($keys = null)
    {
        $data = parent::all();
        $data['company_id'] = $this->route('id');

        return $data;
    }

    /**
     * Application can send invitation.
     *
     * @return bool
     */
    protected function canSendInvitation()
    {
        return $this->checkApplicationSetting(ModuleType::GENERAL_INVITE_ENABLED);
    }
}
