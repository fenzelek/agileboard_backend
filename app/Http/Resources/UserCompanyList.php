<?php

namespace App\Http\Resources;

class UserCompanyList extends AbstractResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->user->id,
            'email' => $this->user->email,
            'first_name' => $this->user->first_name,
            'last_name' => $this->user->last_name,
            'avatar' => $this->user->avatar,
            'company_role_id' => $this->role_id,
            'company_status' => $this->status,
            'company_title' => $this->title,
            'company_skills' => $this->skills,
            'company_description' => $this->description,
        ];
    }
}
