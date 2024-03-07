<!DOCTYPE html>
<html>
<head>
    <title>Lista paragonów</title>
    <style>
        table, th, td {
            border: 1px solid black;
        }
        h1 {
            font-weight: 600;
            font-size: 24px;
            margin: 0 0 20px 0;
            text-align: center;
        }     
        table th, table td {
            padding: 5px;
        }
    </style>
</head>
<body>
    <h1>Lista paragonów -  Podsumowanie zbiorcze</h1>
    @include('pdf._receipt_params')

    <table style="width:100%; border-collapse: collapse; font-size:11px">
        <tr>
            <th>Lp.</th>
            <th>Nazwa</th>
            <th>Cena</th>
            <th>Ilość</th>
            <th>Suma netto</th>
            <th>Suma brutto</th>
            <th>Stawka VAT</th>
        </tr>
        @foreach($receipt_items as $index => $receipt_item)
            <tr>
                <td>{{($index + 1)}}</td>
                <td>{{$receipt_item->name}}</td>
                <td>{{denormalize_price($receipt_item->price_gross)}}zł</td>
                <td>{{$receipt_item->quantity}}</td>
                <td>{{denormalize_price($receipt_item->price_net_sum)}}zł</td>                
                <td>{{denormalize_price($receipt_item->price_gross_sum)}}zł</td>
                <td>{{$receipt_item->vat_rate}}</td>
            </tr>
        @endforeach
    </table>

</body>
</html>
