<!DOCTYPE html>
<html>
<head>
    <title>Lista sprzedaży internetowej</title>
    <style>
        table, th, td {
            border: 1px solid black;
        }
    </style>
</head>
<body>
<div style="margin-bottom:20px">
    <b>Firma:</b> {{$company->name}}<br>
    <b>Data wygenerowania:</b> {{Carbon\Carbon::now()}}<br>
    <b>Łączna kwota brutto:</b> {{denormalize_price($sum)}}zł<br>
</div>

@if (count($params))
    <div style="margin-bottom:20px">
        <b>Parametry</b><br>
        @if (isset($params['date_start']) || isset($params['date_end']))
            <b>Okres:</b>
            @if (isset($params['date_start']))
                od {{$params['date_start']}}
            @endif
            @if (isset($params['date_end']))
                do {{$params['date_end']}}
            @endif
            <br>
        @endif
        @if (isset($params['number']))
            <b>Numer:</b> {{$params['number']}}<br>
        @endif
        @if (isset($params['transaction_number']))
            <b>Numer transakcji:</b> {{$params['transaction_number']}}<br>
        @endif
        @if (isset($params['email']))
            <b>Email:</b> {{$params['email']}}<br>
        @endif
    </div>
@endif

<table style="width:100%; border-collapse: collapse; font-size:11px">
    <tr>
        <th>Lp.</th>
        <th>Nr</th>
        <th>Nr transakcji</th>
        <th>Email</th>
        <th>Utworzono</th>
        <th>Kwota netto</th>
        <th>Kwota brutto</th>
        <th>VAT</th>
    </tr>
    @foreach($online_sales as $index => $sale)
        <tr>
            <td>{{($index + 1)}}</td>
            <td>{{$sale->number}}</td>
            <td>{{$sale->transaction_number}}</td>
            <td>{{$sale->email}}</td>
            <td>{{$sale->sale_date}}</td>
            <td>{{denormalize_price($sale->price_net)}}zł</td>
            <td>{{denormalize_price($sale->price_gross)}}zł</td>
            <td>{{denormalize_price($sale->vat_sum)}}zł</td>
        </tr>
    @endforeach
</table>
</body>
</html>