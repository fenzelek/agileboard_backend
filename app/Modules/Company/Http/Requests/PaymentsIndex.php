<?php

namespace App\Modules\Company\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Other\PaymentStatus;
use Illuminate\Validation\Rule;

class PaymentsIndex extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'status' => [Rule::in([
                PaymentStatus::STATUS_BEFORE_START,
                PaymentStatus::STATUS_NEW,
                PaymentStatus::STATUS_PENDING,
                PaymentStatus::STATUS_COMPLETED,
                PaymentStatus::STATUS_CANCELED,
                PaymentStatus::STATUS_REJECTED,
            ])],
        ];
    }
}
