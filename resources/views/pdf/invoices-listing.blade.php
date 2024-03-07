<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>
        Lista faktur
    </title>
    <style>
        @page {
            footer: page-footer;
        }

        body {
            font-weight: 300;
            font-size: 13px;
            font-family: OpenSans;
        }

        h1 {
            font-weight: 600;
            font-size: 24px;
            margin: 0;

        }

        h2 {
            font-weight: 300;
            font-size: 16px;
            margin: 0;

        }

        table, th, td {
            border: none;
            padding: 10px 5px;
            text-align: left;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-bold {
            font-weight: bold;
        }

        .fs-20 {
            font-size: 20px;
        }

        .border-bottom th,
        .border-bottom td {
            border-bottom: #e7e7e7 solid 2px;
        }

        thead th {
            background-color: #e7e7e7;
        }

        .border-bottom th.no-border,
        .border-bottom td.no-border {
            border: none;
        }

        .signature {
            font-size: 9px;
        }
        .nowrap{
            white-space: nowrap;
        }
    </style>
</head>
<body>
<div>
    <h1 class="text-center">Lista faktur</h1>
    <p>
    @if($request->input('date_start'))
        Od {{ $request->input('date_start') }}
        @if($request->input('date_end'))
             do {{ $request->input('date_end') }}
        @endif
        <br>
    @endif
    @if($request->input('status') && ! \App\Models\Other\SaleInvoice\FilterOption::isAll($request->input('status')))
        Ze statusem: {{ \App\Models\Other\SaleInvoice\FilterOption::translate($request->input('status')) }}<br>
    @endif
    @if($request->input('contractor'))
        Dla kontrahenta: {{ $request->input('contractor') }}
    @endif
    </p>
</div>
<table class="border-bottom">
    <thead>
        <tr>
            <th>L.p.</th>
            <th>Numer</th>
            <th>Data wystawienia</th>
            <th>Kontrahent</th>
            <th>Typ</th>
            <th>Wartość netto</th>
            <th>Wartość brutto</th>
            <th>Termin płatności</th>
            @if(\App\Models\Other\SaleInvoice\FilterOption::isNotPaid($request->input('status')))
                <th>Do zapłaty</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach($invoices as $invoice)
        <tr>
            <td>
                {{ $loop->iteration }}
            </td>
            <td>
                {{ $invoice->number }}
            </td>
            <td>
                {{ $invoice->issue_date }}
            </td>
            <td>
                {{ $invoice->invoiceContractor->name }}
            </td>
            <td>
                {{ $invoice->invoiceType->getDescription($invoice->company->isVatPayer()) }}
            </td>
            <td>
                {{ separators_format_output($invoice->price_net) }}
            </td>
            <td>
                {{ separators_format_output($invoice->price_gross) }}
            </td>
            <td>
                {{ \Carbon\Carbon::parse($invoice->issue_date)->addDays($invoice->payment_term_days)->toDateString() }}
            </td>
            @if(\App\Models\Other\SaleInvoice\FilterOption::isNotPaid($request->input('status')))
                <td class="nowrap">
                    {{ separators_format_output($invoice->payment_left) }}
                </td>
            @endif
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            @include('pdf._partials.invoices_listing.column_padding')
            <td colspan="3" class="text-right text-bold">Suma netto: {{ separators_format_output($invoices->sum('price_net')) }}</td>
        </tr>
        <tr>
            @include('pdf._partials.invoices_listing.column_padding')
            <td colspan="3" class="text-right text-bold">Suma brutto: {{ separators_format_output($invoices->sum('price_gross')) }}</td>
        </tr>
        @if(\App\Models\Other\SaleInvoice\FilterOption::isNotPaid($request->input('status')))
        <tr>
            @include('pdf._partials.invoices_listing.column_padding')
            <td colspan="3" class="text-right text-bold">Pozostaje do zapłaty: {{ separators_format_output($invoices->sum('payment_left')) }}</td>
        </tr>
        @endif
    </tfoot>
</table>

@if($footer == 1)
    <htmlpagefooter name="page-footer">
        <hr>
        <table class="signature">
            <tbody>
            <tr>
                <td class="text-left">Wygenerowano z aplikacji internetowej fvonline.pl</td>
                <td class="text-right">Strona ({PAGENO}/{nb})</td>
            </tr>
            </tbody>
        </table>
    </htmlpagefooter>
@endif
</body>
</html>
