<?php

namespace App\Modules\Company\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Db\Invitation;
use App\Models\Db\User;

class AcceptInvitation extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'token' => ['required'],
            'language' => ['in:en,pl'],
        ];

        if ($this->input('token')) {
            $invitation = Invitation::where('unique_hash', $this->input('token'))->first();
            if ($invitation) {
                $user = User::findByEmail($invitation->email);
                // if no users exists with this email, we will require password
                if (! $user) {
                    $rules['password'] = ['required', 'confirmed', 'min:6'];
                }
            }
        }

        return $rules;
    }
}
