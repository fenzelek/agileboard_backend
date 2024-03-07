<!DOCTYPE html>
<html>
<head>
    <title>Operacja kasowa</title>
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
    </div>

    <div style="margin-bottom:20px">
        <b>Parametry</b><br>
        <b>Data:</b> {{$cash_flow->flow_date}}<br>
        <b>Wystawiający:</b> {{$cash_flow->user->first_name}} {{$cash_flow->user->last_name}}<br>
    </div>

    <table style="width:100%; border-collapse: collapse; font-size:11px">
        <tr>
            <th>Nr dokumentu</th>
            <th>Nr transakcji</th>
            <th>Wartość</th>
            <th>Typ</th>
            <th>Opis</th>
            <th>Utworzono</th>
        </tr>
        <tr>
            <td>
                @if($cash_flow->receipt )
                    {{ $cash_flow->receipt->number }}<br>
                @endif
                @if($cash_flow->invoice)
                    {{ $cash_flow->invoice->number }}
                @endif
            </td>
            <td>{{$cash_flow->receipt ? $cash_flow->receipt->transaction_number : ''}}</td>
            <td>{{denormalize_price($cash_flow->amount)}}zł</td>
            <td>
                @if($cash_flow->direction == 'initial')
                    Stan początkowy
                @elseif($cash_flow->direction == 'in')
                    Kasa przyjęła
                @elseif($cash_flow->direction == 'out')
                    Kasa wydała
                @else
                    Stan końcowy
                @endif
            </td>
            <td>{{$cash_flow->description}}</td>
            <td>{{$cash_flow->flow_date}}</td>
        </tr>
    </table>
</body>
</html>
