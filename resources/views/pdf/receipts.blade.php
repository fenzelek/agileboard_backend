<!DOCTYPE html>
<html>
<head>
    <title>Lista paragonów</title>
    <style>
        table, th, td {
            border: 1px solid black;
        }
    </style>
</head>
<body>
    @include('pdf._receipt_params')

    <table style="width:100%; border-collapse: collapse; font-size:11px">
        <tr>
            <th>Lp.</th>
            <th>Nr</th>
            <th>Nr transakcji</th>
            <th>Utworzono</th>
            <th>Kwota netto</th>
            <th>Kwota brutto</th>
            <th>VAT</th>
            <th>Metoda</th>
        </tr>
        @foreach($receipts as $index => $receipt)
            <tr>
                <td>{{($index + 1)}}</td>
                <td>{{$receipt->number}}</td>
                <td>{{$receipt->transaction_number}}</td>
                <td>{{$receipt->sale_date}}</td>
                <td>{{denormalize_price($receipt->price_net)}}zł</td>
                <td>{{denormalize_price($receipt->price_gross)}}zł</td>
                <td>{{denormalize_price($receipt->vat_sum)}}zł</td>
                <td>{{$receipt->paymentMethod->name}}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
