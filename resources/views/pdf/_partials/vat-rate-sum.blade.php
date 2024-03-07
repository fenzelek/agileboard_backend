@if($invoice->invoiceType->isReverseChargeType())
    ---
@else
    {{ separators_format_output($vat_rate_sum) }}
@endif
