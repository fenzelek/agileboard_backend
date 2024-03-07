<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>
        @if(! empty($invoice->corrected_invoice_id))
            Korekta faktury
        @else
            Faktura
        @endif
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
            font-weight: 600;
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

        td.lp {
            width: 5%;
        }

        td.name {
            width: 40%;
        }

        .signature {
            font-size: 9px;
        }

        .title {
            font-weight: 600;
            font-size: 14px;
            padding: 5px;
        }

        .bg-silver {
            background-color: #e7e7e7;
        }

        .address {
            padding: 10px 5px;
        }

        .col-offset {
            width: 33%;
        }

        .row-offset {
            padding: 15px 0;
        }

        .col-6 {
            width: 48%;
            float: left;
        }

        td.col-width {
            width: 48%;
        }

        .col-margin {
            margin-left: 4%;
        }
        .fixed-table{
            table-layout: fixed;
        }
        .width-half{
            width: 50%;
            vertical-align: top;
        }
        .no-space{
            margin: 0;
            padding: 0;
        }
        .no-tb-space{
            margin-top: 0;
            margin-bottom: 0;
            padding-top: 0;
            padding-bottom: 0;
        }
        .logotype{
            max-width: 100px;
            max-height: 100px;
        }
        .vertical-top{
            vertical-align: top;
        }
        .quantity{
            white-space: nowrap;
        }
    </style>
</head>
<body>
<div class="col-6">
    <table>
        <tr>
            <td class="title bg-silver">Sprzedawca</td>
        </tr>
        <tr>
            <td class="address">
                <table class="no-space">
                    <tr class="no-space">
                        @if($invoice->invoiceCompany->logotype && file_exists(storage_path('logotypes/' . $invoice->invoiceCompany->logotype)))
                        <td class="no-space vertical-top" style="width: 100px; padding-right: 15px;">
                            <img class="logotype" src="{{ storage_path('logotypes/' . $invoice->invoiceCompany->logotype) }}" alt="Logo">
                        </td>
                        @endif
                        <td class="no-space vertical-top">
                            {{ $invoice->invoiceCompany->name }}<br>
                            {{ $invoice->invoiceCompany->main_address_street }}
                            &nbsp;{{ $invoice->invoiceCompany->main_address_number }}<br>
                            {{ $invoice->invoiceCompany->main_address_zip_code }}
                            &nbsp;{{ $invoice->invoiceCompany->main_address_city }}<br>
                            @if(strtolower($invoice->invoiceCompany->main_address_country) != 'polska')
                                {{ $invoice->invoiceCompany->main_address_country }}<br>
                            @endif
                            NIP:&nbsp;{{ $invoice->invoiceCompany->full_vatin }}<br>
                            Tel.:&nbsp;{{ $invoice->invoiceCompany->phone }}<br>
                            <a href="{{ $invoice->invoiceCompany->website }}">{{ $invoice->invoiceCompany->website }}</a><br>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="row-offset no-border"></td>
        </tr>
    </table>
</div>
<div class="col-6 col-margin">
    <h1 class="text-left">{{ $invoice->invoiceType->getTitle($invoice->company->isVatPayer())  }}
        @if($duplicate)
            &nbsp;Duplikat
        @endif
    </h1>
    <h2 class="text-left">nr {{ $invoice->number }}</h2>
    <p class="text-left" style="margin-bottom: 0; text-indent: -20px;">
        @if($invoice->receipts->count())
            @if($invoice->receipts->count() > 1)
                Do paragonów: {{ $invoice->receipts->pluck('number')->implode(', ') }}<br>
            @else
                Do paragonu: {{ $invoice->receipts->first()->number }}<br>
            @endif
        @endif
        @if(! empty($invoice->proforma_id))
            Do proformy: {{ $invoice->proforma->number }}
        @endif
    </p>
    <p class="text-left" style="margin: 0;">
        @if(! empty($invoice->corrected_invoice_id))
            Data wystawienia:&nbsp;{{ $invoice->issue_date }} <br>
            Data korekty:&nbsp;{{ $invoice->sale_date }}<br>
            Do dokumentu nr:&nbsp;{{ $invoice->correctedInvoice->number }}<br>
            Wystawionego w dniu:&nbsp;{{ $invoice->correctedInvoice->issue_date }}<br>
            @if($invoice->receipts->count() > 1)
                Z datą sprzedaży:&nbsp;{{ $invoice->correctedInvoice->issue_date }}
            @else
                Z datą sprzedaży:&nbsp;{{ $invoice->correctedInvoice->sale_date }}
            @endif
            <br>
        @else
            Data wystawienia:&nbsp;{{ $invoice->issue_date }}<br>
            @if(! $invoice->invoiceType->isType(\App\Models\Other\InvoiceTypeStatus::FINAL_ADVANCE))
                @if(! empty($invoice->proforma_id))
                    Data otrzymania zaliczki:&nbsp;{{ $invoice->sale_date }}
                @elseif($invoice->receipts->count() > 1)
                    Data sprzedaży:&nbsp;{{ $invoice->issue_date }}
                @else
                    Data sprzedaży:&nbsp;{{ $invoice->sale_date }}
                @endif
                <br>
            @endif
        @endif
        @if($duplicate)
            Data wydruku duplikatu:&nbsp;{{ \Carbon\Carbon::now()->format('Y-m-d') }}<br>
        @endif
        <br>
    </p>
</div>

<table>
    <tr>
        <td class="title bg-silver col-width">Nabywca</td>
        <td></td>
        @if($invoice->invoiceDeliveryAddress)
            <td class="title bg-silver text-right col-width">Odbiorca</td>
        @else
            <td class="col-width"></td>
        @endif
    </tr>
    <tr>
        <td class="address">
            {{ $invoice->invoiceContractor->name }}<br>
            {{ $invoice->invoiceContractor->main_address_street }}
            &nbsp;{{ $invoice->invoiceContractor->main_address_number }}<br>
            {{ $invoice->invoiceContractor->main_address_zip_code }}
            &nbsp;{{ $invoice->invoiceContractor->main_address_city }}<br>
            @if(strtolower($invoice->invoiceContractor->main_address_country) != 'polska')
                {{ $invoice->invoiceContractor->main_address_country }}<br>
            @endif
            @if(! empty($invoice->invoiceContractor->vatin))
                NIP:&nbsp;{{ $invoice->invoiceContractor->full_vatin }}<br>
            @endif
        </td>
        <td></td>
        @if($invoice->invoiceDeliveryAddress)
            <td class="address text-right">
                {{ $invoice->invoiceDeliveryAddress->receiver_name }}<br>
                {{ $invoice->invoiceDeliveryAddress->street }}&nbsp;{{ $invoice->invoiceDeliveryAddress->number }}
                <br>
                {{ $invoice->invoiceDeliveryAddress->zip_code}}&nbsp;{{ $invoice->invoiceDeliveryAddress->city }}
                <br>
                @if( strtolower($invoice->invoiceDeliveryAddress->country) != 'polska' )
                    {{ $invoice->invoiceDeliveryAddress->country }}
                @endif
            </td>
        @else
            <td class="col-width"></td>
        @endif
    </tr>
    @if(! empty($invoice->description))
        <tr>
            <td colspan="3">Opis: {{ $invoice->description }}</td>
        </tr>
    @endif
    <tr>
        <td class="row-offset no-border"></td>
    </tr>
</table>
@if(! empty($invoice->invoice_margin_procedure_id))
    <p>{{ ucfirst($invoice->invoiceMarginProcedure->description) }}</p>
@endif

@if(! empty($invoice->corrected_invoice_id))

    <p>Przyczyna korekty: {{ \App\Models\Other\InvoiceCorrectionType::all($invoice->company)[$invoice->correction_type] }}</p>
    <p>Przed korektą</p>
    <table class="border-bottom">
        <thead>
        <tr>
            <th>Lp</th>
            <th>Usługa/Towar</th>
            <th class="text-right">PKWIU</th>
            <th class="text-right">Ilość</th>
            <th class="text-right">J.m.</th>
            <th class="text-right">
                @if($invoice->gross_counted)
                    @if($invoice->company->isVatPayer())
                        Cena brutto
                    @else
                        Cena jednostkowa
                    @endif
                @else
                    Cena netto
                @endif
            </th>
            @if($invoice->printTaxDetails())
                <th class="text-right">Wartość netto</th>
                <th class="text-right">VAT</th>
            @else
                <th colspan="2" class="text-right"></th>
            @endif
            <th class="text-right">Wartość @if($invoice->company->isVatPayer())brutto @endif</th>
        </tr>
        </thead>
        <tbody>
        @php($lp = 1)
        @foreach($invoice->items as $item)
            @if(! empty($item->position_corrected_id))
                <tr>
                    <td class="lp">{{ $lp++ }}</td>
                    <td class="name">{{ $item->positionCorrected->print_name }}</td>
                    <td class="pkwiu text-right">{{ $item->positionCorrected->pkwiu }}</td>
                    <td class="quantity text-right">{{ formatted_quantity($item->positionCorrected->quantity) }}</td>
                    <td class="text-right">{{ $item->positionCorrected->serviceUnit->slug }}</td>
                    <td class="text-right">
                        @if($invoice->gross_counted)
                            {{ separators_format_output($item->positionCorrected->price_gross) }}
                        @else
                            {{ separators_format_output($item->positionCorrected->price_net) }}
                        @endif
                    </td>
                    @if($invoice->printTaxDetails())
                        <td class="text-right">{{ separators_format_output($item->positionCorrected->price_net_sum) }}</td>
                        @include('pdf._partials.vat-rate-name', ['vat_rate_name' => $item->positionCorrected->vatRate->name])
                    @else
                        <td colspan="2" class="text-right"></td>
                    @endif
                    <td class="text-right">{{ separators_format_output($item->positionCorrected->price_gross_sum) }}</td>
                </tr>
                @if($item->positionCorrected->print_on_invoice)
                    <tr>
                        <td colspan="9">Opis: {{ $item->positionCorrected->description }}</td>
                    </tr>
                @endif
            @endif
        @endforeach
        </tbody>
    </table>
    <p>Po korekcie</p>
@endif
<table class="border-bottom">
    <thead>
    <tr>
        <th>Lp</th>
        <th>Usługa/Towar</th>
        <th class="text-right">PKWIU</th>
        <th class="text-right">Ilość</th>
        <th class="text-right">J.m.</th>
        <th class="text-right">
            @if($invoice->gross_counted)
                @if($invoice->company->isVatPayer())
                    Cena brutto
                @else
                    Cena jednostkowa
                @endif
            @else
                Cena netto
            @endif
        </th>
        @if($invoice->printTaxDetails())
            <th class="text-right">Wartość netto</th>
            <th class="text-right">VAT</th>
        @else
            <th colspan="2" class="text-right"></th>
        @endif
        <th class="text-right">Wartość @if($invoice->company->isVatPayer())brutto @endif</th>
    </tr>
    </thead>
    <tbody>
    @foreach($invoice->items as $item)
        <tr>
            <td class="lp">{{ $loop->iteration }}</td>
            <td class="name">{{ $item->print_name }}</td>
            <td class="pkwiu text-right">{{ $item->pkwiu }}</td>
            <td class="quantity text-right">{{ formatted_quantity($item->quantity) }}</td>
            <td  class="text-right">{{ $item->serviceUnit->slug }}</td>
            <td class="text-right">
                @if($invoice->gross_counted)
                    {{ separators_format_output($item->price_gross) }}
                @else
                    {{ separators_format_output($item->price_net) }}
                @endif
            </td>
            @if($invoice->printTaxDetails())
                <td class="text-right">{{ separators_format_output($item->price_net_sum) }}</td>
                @include('pdf._partials.vat-rate-name', ['vat_rate_name' => $item->vatRate->name])
            @else
                <td colspan="2" class="text-right"></td>
            @endif
            <td class="text-right">{{ separators_format_output($item->price_gross_sum) }}</td>
        </tr>
        @if($item->print_on_invoice)
            <tr>
                <td colspan="9">Opis: {{ $item->description }}</td>
            </tr>
        @endif
    @endforeach
    <tr>
        <td class="row-offset no-border"></td>
    </tr>
    </tbody>
</table>
<table class="fixed-table">
    <tbody>
        <tr>
            <td class="width-half">
                @if($invoice->receipts->count())
                    <table>
                        <tbody>
                        <tr>
                            <td class="no-border text-bold bg-silver">Forma płatności</td>
                            <td class="no-border text-bold bg-silver">Termin</td>
                            <td class="no-border text-bold bg-silver">Kwota</td>
                        </tr>
                        @foreach($invoice->receipts as $receipt)
                            <tr>
                                <td>{{ $receipt->paymentMethod->name }}</td>
                                <td>{{ \Carbon\Carbon::parse($receipt->sale_date)->toDateString() }}</td>
                                <td>{{ separators_format_output($receipt->price_gross) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @elseif($special_payment = $invoice->specialPayments()->latest()->first())
                    <table>
                        <tbody>
                        <tr>
                            <td class="no-border text-bold bg-silver">Forma płatności</td>
                            <td class="no-border text-bold bg-silver">Termin</td>
                            <td class="no-border text-bold bg-silver">Kwota</td>
                        </tr>
                        <tr>
                            <td>{{ $invoice->paymentMethod->name }}</td>
                            <td>{{ \Carbon\Carbon::parse($invoice->issue_date)->addDays($invoice->payment_term_days)->toDateString() }}</td>
                            <td>{{ separators_format_output($invoice->price_gross - $special_payment->amount) }}</td>
                        </tr>
                        <tr>
                            <td>{{ $special_payment->paymentMethod->name }}</td>
                            <td>{{ \Carbon\Carbon::parse($invoice->issue_date)->toDateString() }}</td>
                            <td>{{ separators_format_output($special_payment->amount) }}</td>
                        </tr>
                        </tbody>
                    </table>
                @endif
                @if(! $invoice->receipts->count())
                    Sposób płatności: {{ $invoice->paymentMethod->name }}<br>
                    Termin
                    płatności: {{ \Carbon\Carbon::parse($invoice->issue_date)->addDays($invoice->payment_term_days)->toDateString() }}<br>
                    @if ($bank_info)
                        Bank: {{ $invoice->invoiceCompany->bank_name }}<br>
                        Nr rachunku: {{ nonBreakableSpaces($invoice->invoiceCompany->bank_account_number) }}<br>
                    @endif
                @endif
                <br>
            </td>
            <td class="width-half">
                <table>
                    <tbody>
                    <tr>
                        <td class="text-bold bg-silver">Razem</td>
                        @if($invoice->printTaxDetails())
                            <td class="text-right text-bold bg-silver">{{ separators_format_output($invoice->price_net) }}</td>
                            <td class="text-right text-bold bg-silver">X</td>
                            <td class="text-right text-bold bg-silver">
                                @include('pdf._partials.vat-rate-sum', ['vat_rate_sum' => $invoice->vat_sum])
                            </td>
                        @else
                            <td colspan="3" class="text-right text-bold bg-silver"></td>
                        @endif
                        <td class="text-right text-bold bg-silver">{{ separators_format_output($invoice->price_gross) }}</td>
                    </tr>
                    @if($invoice->printTaxDetails())
                        @foreach($invoice->fullPrintTaxes() as $tax)
                            <tr>
                                <td>
                                    @if($loop->first)
                                        w tym
                                    @endif
                                </td>
                                <td class="text-right">{{ separators_format_output($tax->price_net) }}</td>
                                @include('pdf._partials.vat-rate-name', ['vat_rate_name' => $tax->vatRate->name])
                                <td class="text-right">
                                    @include('pdf._partials.vat-rate-sum', ['vat_rate_sum' => $tax->price_gross - $tax->price_net])
                                </td>
                                <td class="text-right">{{ separators_format_output($tax->price_gross) }}</td>
                            </tr>
                        @endforeach
                    @endif
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>


@if($invoice->invoiceType->isType(\App\Models\Other\InvoiceTypeStatus::FINAL_ADVANCE))
<table class="fixed-table">
    <tbody>
    <tr>
        <td class="width-half">
            <p><strong>Poprzednie zaliczki</strong></p>
            <table>
                <tbody>
                <tr>
                        <td class="text-bold bg-silver">Lp</td>
                        <td class="text-right text-bold bg-silver">Numer faktury</td>
                        <td class="text-right text-bold bg-silver">Data</td>
                        <td class="text-right text-bold bg-silver">Netto</td>
                        <td class="text-right text-bold bg-silver">Brutto</td>
                </tr>
                @foreach($invoice->advanceInvoicesIncluded() as $advance)
                <tr>
                    <td class="text-bold ">{{ $loop->iteration  }}</td>
                    <td class="text-right text-bold ">{{ $advance->number }}</td>
                    <td class="text-right text-bold ">{{ $advance->issue_date }}</td>
                    <td class="text-right text-bold ">{{ separators_format_output($advance->price_net) }}</td>
                    <td class="text-right text-bold ">{{ separators_format_output($advance->price_gross) }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </td>
        <td class="width-half">
            <p><strong>Rozliczenie zaliczki wg stawek</strong></p>
            <table>
                <tbody>
                <tr>
                    <td class="text-right text-bold bg-silver">Cena netto</td>
                    <td class="text-right text-bold bg-silver">VAT</td>
                    <td class="text-right text-bold bg-silver">VAT </td>
                    <td class="text-right text-bold bg-silver">Wartość @if($invoice->company->isVatPayer())brutto @endif</td>
                </tr>
                @foreach($invoice->taxes as $tax)
                    <tr>
                        <td class="text-right">{{ separators_format_output($tax->price_net) }}</td>
                        @include('pdf._partials.vat-rate-name', ['vat_rate_name' => $tax->vatRate->name])
                        <td class="text-right">
                            @include('pdf._partials.vat-rate-sum', ['vat_rate_sum' => $tax->price_gross - $tax->price_net])
                        </td>
                        <td class="text-right">{{ separators_format_output($tax->price_gross) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </td>
    </tr>
    </tbody>
</table>
@endif

<table class="fixed-table">
    <tbody>
    <tr>
        <td class="width-half">

        </td>
        <td class="width-half">
            <table>
                <tbody>
                <tr>
                    <td class="no-border" colspan="2">

                        @if($invoice->price_gross > 0)
                            @if($invoice->isPaid())
                                Zapłacono
                            @else
                                Razem do zapłaty
                            @endif
                        @else
                            Razem do zwrotu
                        @endif
                    </td>
                    <td class="no-border text-bold text-right fs-20" colspan="3">
                        {{ separators_format_output(abs($invoice->price_gross)) }}&nbsp;zł
                    </td>
                </tr>
                @if($invoice->hasIncompleteSpecialPayments())
                <tr>
                    <td class="no-border" colspan="2">Pozostało</td>
                    <td class="no-border text-right" colspan="3">
                        {{ separators_format_output(abs($invoice->price_gross - $invoice->specialPaymentsAmount())) }}&nbsp;zł
                    </td>
                </tr>
                @endif
                <tr>
                    @if($invoice->invoiceType->isReverseChargeType() and $invoice->printTaxDetails())
                        <td colspan="5" class="no-border text-right">*) - odwrotne obciążenie</td>
                    @else
                        <td class="row-offset no-border"></td>
                    @endif
                </tr>
                <tr>
                    <td class="row-offset no-border"></td>
                </tr>
                <tr>
                    <td class="row-offset no-border"></td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    </tbody>
</table>

<table class="signature">
    <tbody>
    <tr>
        <td class="text-center">
            {{ $invoice->drawer->first_name }} {{ $invoice->drawer->last_name }}
            <br>.......................................
            <br>Sprzedawca
        </td>
        <td class="col-offset"></td>
        <td class="text-center">
            <br>.......................................
            <br>Nabywca
        </td>
    </tr>
    </tbody>
</table>
@if($footer == 1)
    <htmlpagefooter name="page-footer">
        <hr>
        <table class="signature">
            <tbody>
            <tr>
                <td class="text-left">Wygenerowano z aplikacji internetowej fvonline.pl</td>
                <td class="text-right">
                    <p class="text-left" style="margin: 0;">
                        {{ $invoice->invoiceType->getTitle($invoice->company->isVatPayer())  }}
                        @if($duplicate)
                            &nbsp;Duplikat
                        @endif
                        &nbsp;nr {{ $invoice->number }}
                    </p>
                </td>
            </tr>
            <tr>
                <td class="text-right no-tb-space" colspan="2"><small>Strona ({PAGENO}/{nb})</small></td>
            </tr>
            </tbody>
        </table>
    </htmlpagefooter>
@endif
</body>
</html>
