<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rejestr faktur</title>
    <style>
        body{
            font-weight: 300;
            font-size: 13px;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif
        }
        table, th, td {
            border: none;
            padding: 10px 5px;
            text-align: left;
            font-size: 11px;
        }
        table{
            width:100%;
            border-collapse: collapse;
        }
        .text-center{
            text-align: center;
        }
        .text-right{
            text-align: right;
        }
        .text-bold{
            font-weight: 600;
        }
        .fs-20{
            font-size: 20px;
        }
        .border-bottom th,
        .border-bottom td{
            border-bottom: #e7e7e7 solid 2px;
        }
        .border-bottom th.no-border,
        .border-bottom td.no-border{
            border: none;
        }
        td.lp{
            width: 5%;
        }
        td.date{
            width: 10%;
        }
        td.number{
            width: 15%;
        }
        td.summery{
            width: 8%;
        }
        .signature{
            font-size: 9px;
        }
        .bg-silver{
            background-color: #e7e7e7;
        }
        .row-offset{
            padding: 15px 0;
        }
    </style>
</head>
<body>
<table class="border-bottom">
    <thead>
    <tr>
        <td class="no-border text-center fs-20" colspan="9">
            Zapisy w rejestrze faktur do rozliczenia
            <br>w deklaracji VAT-7
            @if($year)w {{ $year }}@endif
            @if($month)/{{ $month }}@endif
        </td>
    </tr>
    <tr>
        <td class="no-border" colspan="4">
            {{ $company->name }}<br>
            {{ $company->main_address_street }}&nbsp;{{ $company->main_address_number }}<br>
            {{ $company->main_address_zip_code }}&nbsp;{{ $company->main_address_city }}<br>
        </td>
        <td class="no-border text-right" colspan="5">
            Data wydruku: {{ \Carbon\Carbon::now()->format('Y-m-d') }}
        </td>
    </tr>
    <tr>
        <td class="row-offset no-border"></td>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td class="text-bold">Lp</td>
        <td class="text-bold">Data</td>
        <td class="text-bold">Nr dokumentu</td>
        <td class="text-bold" colspan="2">Kontrahent</td>
        <td class="text-right text-bold">Stawka</td>
        <td class="text-right text-bold">Netto</td>
        <td class="text-right text-bold">Kwota Vat</td>
        <td class="text-right text-bold">Brutto</td>
    </tr>
    @foreach($invoices as $invoice)
        <tr>
            <td class="lp" @if($invoice->taxes->count()) rowspan="{{ $invoice->taxes->count() }}" @endif>
                {{ $loop->iteration }}
            </td>
            <td class="date" @if($invoice->taxes->count()) rowspan="{{ $invoice->taxes->count() }}" @endif>
                {{ $invoice->sale_date }}
            </td>
            <td class="number" @if($invoice->taxes->count()) rowspan="{{ $invoice->taxes->count() }}" @endif>
                {{ $invoice->number }}
            </td>
            <td colspan="2" @if($invoice->taxes->count()) rowspan="{{ $invoice->taxes->count() }}" @endif>
                {{ $invoice->invoiceContractor->name }}<br>
                {{ $invoice->invoiceContractor->main_address_street }}&nbsp;{{ $invoice->invoiceContractor->main_address_number }}<br>
                {{ $invoice->invoiceContractor->main_address_zip_code }}&nbsp;{{ $invoice->invoiceContractor->main_address_city }}<br>
                @if(! empty($invoice->invoiceContractor->vatin))
                    NIP:&nbsp;{{ $invoice->invoiceContractor->fullVatin }}<br>
                @endif
            </td>
                @if($invoice->taxes->count())
                    @foreach($invoice->taxes as $tax)
                        @if(!$loop->first)
                            <tr>
                        @endif
                        <td class="text-right @if(!$loop->last) no-border @endif">{{ $tax->vatRate->name }}</td>
                        <td class="text-right @if(!$loop->last) no-border @endif">{{ separators_format_output($tax->price_net) }}</td>
                        <td class="text-right @if(!$loop->last) no-border @endif">{{ separators_format_output($tax->price_gross - $tax->price_net) }}</td>
                        <td class="text-right @if(!$loop->last) no-border @endif">{{ separators_format_output($tax->price_gross) }}</td>
                        @if(!$loop->first)
                            </tr>
                        @endif
                    @endforeach
                @endif
        </tr>
    @endforeach
    <tr>
        <td class="row-offset no-border"></td>
    </tr>
    <tr>
        <td class="no-border" colspan="4"></td>
        <td class="text-bold bg-silver summery">Razem</td>
        <td class="text-right text-bold bg-silver">X</td>
        <td class="text-right text-bold bg-silver">{{ separators_format_decimal($report['price_net']) }}</td>
        <td class="text-right text-bold bg-silver">{{ separators_format_decimal($report['vat_sum']) }}</td>
        <td class="text-right text-bold bg-silver">{{ separators_format_decimal($report['price_gross']) }}</td>
    </tr>
    @foreach($report['vat_rates'] as $vat_rate)
        <tr>
            <td class="no-border" colspan="4"></td>
            <td class="summery">
                @if($loop->first)
                    w tym
                @endif
            </td>
            <td class="text-right">{{ $vat_rate['vat_rate_name'] }}</td>
            <td class="text-right">{{ separators_format_decimal($vat_rate['price_net']) }}</td>
            <td class="text-right">{{ separators_format_decimal($vat_rate['vat_sum']) }}</td>
            <td class="text-right">{{ separators_format_decimal($vat_rate['price_gross']) }}</td>
        </tr>
    @endforeach
    <tr>
        <td class="row-offset no-border"></td>
    </tr>
    <tr>
        <td class="row-offset no-border"></td>
    </tr>
    <tr>
        <td class="row-offset no-border"></td>
    </tr>
    </tbody>

</table>

<p class="text-right signature">
    ...............................................................
    <br>Imię i nazwisko osoby sporządzającej
</p>
</body>
</html>
