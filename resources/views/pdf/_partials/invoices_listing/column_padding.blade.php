@if(\App\Models\Other\SaleInvoice\FilterOption::isNotPaid($request->input('status')))
    <td colspan="6" class="no-border"></td>
@else
    <td colspan="5" class="no-border"></td>
@endif
