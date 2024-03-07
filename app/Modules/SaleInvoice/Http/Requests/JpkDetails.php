<?php

namespace App\Modules\SaleInvoice\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class JpkDetails extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'regon' => ['present', 'nullable', 'regex:#^(\d{9}|\d{14})$#'],
            'state' => ['required', 'string', 'max:255'],
            'county' => ['required', 'string', 'max:255'],
            'community' => ['required', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'building_number' => ['present', 'string', 'nullable', 'max:255'],
            'flat_number' => ['present', 'string', 'nullable', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:7'],
            'postal' => ['required', 'string', 'max:255'],
            'tax_office_id' => ['required', 'integer', Rule::exists('tax_offices', 'id')],
        ];
    }
}
