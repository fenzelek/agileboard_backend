<td class="text-right">
@if($invoice->invoiceType->isReverseChargeType())
    --- *
@else
    {{ $vat_rate_name }}
@endif
</td>