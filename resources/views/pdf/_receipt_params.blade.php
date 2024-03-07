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
        @if (isset($params['payment_method']))
            <b>Metoda płatności:</b> {{$params['payment_method']->name}}<br>
        @endif
        @if (isset($params['number']))
            <b>Numer:</b> {{$params['number']}}<br>
        @endif
        @if (isset($params['transaction_number']))
            <b>Numer transakcji:</b> {{$params['transaction_number']}}<br>
        @endif
        @if (isset($params['user']))
            <b>Wystawiający:</b> {{$params['user']->first_name}} {{$params['user']->last_name}}<br>
        @endif
    </div>
@endif