<?php

namespace App\Modules\Company\Traits;

use App\Models\Other\VatReleaseReasonType;
use App\Models\Db\VatReleaseReason;
use Illuminate\Validation\Rule;

trait VatPayerRules
{
    protected function vatPayerCommonRules()
    {
        $legal_basis_type = VatReleaseReason::findBySlug(VatReleaseReasonType::LEGAL_BASIS);

        $rules = [
            'vat_payer' => ['required', 'boolean'],
            'vat_release_reason_id' => ['nullable', Rule::exists('vat_release_reasons', 'id')],
            'vat_release_reason_note' => ['nullable', 'string', 'max:1000','required_if:vat_release_reason_id,' .
                $legal_basis_type->id,
            ],
        ];

        if ($this->vat_payer === false) {
            $rules ['vat_release_reason_id'][] = 'required';
        }

        return $rules;
    }
}
