<!DOCTYPE html>
<html>
<head>
    <title>Lista operacji kasowych</title>
    <style>
        table, th, td {
            border: 1px solid black;
        }

        h1 {
            font-weight: 600;
            font-size: 24px;
            margin: 0;
            text-align: center;
        }
        h2 {
            font-weight: 600;
            font-size: 18px;
            margin: 10px 0 0 0;
            text-align: center;
        }
    </style>
</head>
<body>
@if ($cashless == 1)
    <h1>Operacje kasowe - Bezgotówkowe</h1>
@elseif ($cashless == 0)
    <h1>Operacje kasowe - Gotówkowe</h1>
@endif

@if ($balanced == 1)
    <h2>wydruk zbilansowany</h2>
@endif

<div style="margin-bottom:20px; margin-top: 20px;">
    <b>Firma:</b> {{$company->name}}<br>
    <b>Data wygenerowania:</b> {{Carbon\Carbon::now()}}<br>
</div>

<div style="margin-bottom:20px">
    <b>Statystyki:</b><br>
    <b>Stan początkowy:</b> {{$report['cash_initial_sum']}}zł<br>
    <b>Stan końcowy:</b> {{$report['cash_final_sum']}}zł<br>
    <b>Kasa wydała:</b> {{$report['cash_out_sum']}}zł<br>
    <b>Kasa przyjęła:</b> {{$report['cash_in_sum']}}zł<br>
    <b>Wyliczone:</b> {{$report['calc_final_sum']}}zł<br>
</div>

@if (count($params))
    <div style="margin-bottom:20px">
        <b>Parametry</b><br>
        @if (isset($params['date']))
            <b>Data:</b> {{$params['date']}}<br>
        @endif
        @if (isset($params['user']))
            <b>Wystawiający:</b> {{$params['user']->first_name}} {{$params['user']->last_name}}<br>
        @endif
    </div>
@endif

<table style="width:100%; border-collapse: collapse; font-size:11px">
    <tr>
        <th>Lp.</th>
        <th>Nr dokumentu</th>
        <th>Nr transakcji</th>
        <th>Wartość</th>
        <th>Typ</th>
        <th>Opis</th>
        <th>Utworzono</th>
    </tr>
    @foreach($cash_flows as $index => $cash_flow)
        <tr>
            <td>{{($index + 1)}}</td>
            <td>
                @if($cash_flow->receipt )
                    {{ $cash_flow->receipt->number }}<br>
                @endif
                @if($cash_flow->invoice)
                    {{ $cash_flow->invoice->number }}
                @endif
            </td>
            <td>{{$cash_flow->receipt ? $cash_flow->receipt->transaction_number : ''}}</td>
            <td>
                @if (isset($cash_flow->balanced_summary) && $cash_flow->balanced_summary->balanced_records > 1)
                    {{denormalize_price((abs($cash_flow->balanced_summary->sum)))}}zł
                @else
                    {{denormalize_price($cash_flow->amount)}}zł
                @endif
            </td>
            <td>
                @if (isset($cash_flow->balanced_summary) && $cash_flow->balanced_summary->balanced_records > 1)
                    @if($cash_flow->balanced_summary->sum < 0)
                        Kasa wydała
                    @else
                        Kasa przyjęła
                    @endif
                @else
                    @if($cash_flow->direction == 'initial')
                        Stan początkowy
                    @elseif($cash_flow->direction == 'in')
                        Kasa przyjęła
                    @elseif($cash_flow->direction == 'out')
                        Kasa wydała
                    @else
                        Stan końcowy
                    @endif
                @endif
            </td>
            <td>{{$cash_flow->description}}</td>
            <td>{{$cash_flow->flow_date}}</td>
        </tr>
    @endforeach
</table>
</body>
</html>
