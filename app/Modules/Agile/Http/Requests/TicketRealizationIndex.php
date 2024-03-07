<?php

namespace App\Modules\Agile\Http\Requests;

use App\Http\Requests\Request;

class TicketRealizationIndex extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'from' => ['required','date'],
            'limit' => ['int','min:1','max:31'],
        ];
    }
}
