<tr>
    <td  valign="middle">Lp</td>
    <td  valign="middle">Data</td>
    <td  valign="middle">Nr dokumentu</td>
    <td  valign="middle">Nazwa Kontrahenta</td>
    <td  valign="middle">Ulica</td>
    <td  valign="middle">Numer domu</td>
    <td  valign="middle">Kod pocztowy</td>
    <td  valign="middle">Miejscowość</td>
    <td  valign="middle">Kraj</td>
    <td  valign="middle">NIP</td>
    <td  valign="middle">Stawka</td>
    <td  valign="middle">Netto</td>
    <td  valign="middle">Kwota Vat</td>
    <td  valign="middle">Brutto</td>
</tr>
@foreach($invoices as $invoice)
    @php($lp = $loop->iteration)
    <tr >
        <td  valign="middle">
            {{ $lp }}
        </td>
        <td  valign="middle">
            {{ $invoice->sale_date }}
        </td>
        <td  valign="middle">
            {{ $invoice->number }}
        </td>
        <td  valign="middle">
            {{ $invoice->invoiceContractor->name }}
        </td>
        <td  valign="middle">
            {{ $invoice->invoiceContractor->main_address_street }}
        </td>
        <td  valign="middle">
            {{ $invoice->invoiceContractor->main_address_number }}
        </td>
        <td  valign="middle">
            {{ $invoice->invoiceContractor->main_address_zip_code }}
        </td>
        <td  valign="middle">
            {{ $invoice->invoiceContractor->main_address_city }}
        </td>
        <td  valign="middle">
            {{ $invoice->invoiceContractor->main_address_country }}
        </td>
        <td  valign="middle">
            {{ $invoice->invoiceContractor->fullVatin }}
        </td>
    @foreach($invoice->taxes as $tax)
        @if(!$loop->first)
            <tr>
                <td  valign="middle">
                    {{ $lp }}
                </td>
                <td  valign="middle">
                    {{ $invoice->sale_date }}
                </td>
                <td  valign="middle">
                    {{ $invoice->number }}
                </td>
                <td  valign="middle">
                    {{ $invoice->invoiceContractor->name }}
                </td>
                <td  valign="middle">
                    {{ $invoice->invoiceContractor->main_address_street }}
                </td>
                <td  valign="middle">
                    {{ $invoice->invoiceContractor->main_address_number }}
                </td>
                <td  valign="middle">
                    {{ $invoice->invoiceContractor->main_address_zip_code }}
                </td>
                <td  valign="middle">
                    {{ $invoice->invoiceContractor->main_address_city }}
                </td>
                <td  valign="middle">
                    {{ $invoice->invoiceContractor->main_address_country }}
                </td>
                <td  valign="middle">
                    {{ $invoice->invoiceContractor->fullVatin }}
                </td>
                @endif
                <td  valign="middle">{{ $tax->vatRate->name }}</td>
                <td  valign="middle">{{ denormalize_price($tax->price_net) }}</td>
                <td  valign="middle">{{ denormalize_price($tax->price_gross - $tax->price_net) }}</td>
                <td  valign="middle">{{ denormalize_price($tax->price_gross) }}</td>
                @if(!$loop->first)
            </tr>
            @endif
            @endforeach
            @endforeach
