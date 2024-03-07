<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | such as the size rules. Feel free to tweak each of these messages.
    |
    */

    'accepted' => 'Pole musi zostać zaakceptowane.',
    'active_url' => 'Pole jest nieprawidłowym adresem URL.',
    'after' => 'Pole musi być datą późniejszą od :date.',
    'after_or_equal' => 'To pole musi być datą nie wcześniejszą niż :date.',
    'alpha' => 'Pole może zawierać jedynie litery.',
    'alpha_dash' => 'Pole może zawierać jedynie litery, cyfry i myślniki.',
    'alpha_num' => 'Pole może zawierać jedynie litery i cyfry.',
    'array' => 'Pole musi być tablicą.',
    'before' => 'Pole musi być datą wcześniejszą od :date.',
    'before_or_equal' => 'Pole musi być datą nie późniejszą od :date.',
    'between' => [
        'numeric' => 'Pole musi zawierać się w granicach :min - :max.',
        'file' => 'Pole musi zawierać się w granicach :min - :max kilobajtów.',
        'string' => 'Pole musi zawierać się w granicach :min - :max znaków.',
        'array' => 'Pole musi składać się z :min - :max elementów.',
    ],
    'boolean' => 'Pole musi mieć wartość prawda albo fałsz',
    'confirmed' => 'Potwierdzenie pola nie zgadza się.',
    'date' => 'Pole nie jest prawidłową datą.',
    'date_format' => 'Pole nie jest w formacie :format.',
    'different' => 'Pole oraz :other muszą się różnić.',
    'digits' => 'Pole musi składać się z :digits cyfr.',
    'digits_between' => 'Pole musi mieć od :min do :max cyfr.',
    'dimensions' => 'Pole ma niepoprawne wymiary.',
    'distinct' => 'Pole ma zduplikowane wartości.',
    'email' => 'Format pola jest nieprawidłowy.',
    'exists' => 'Wybrano nieprawidłową wartość.',
    'file' => 'Pole musi być plikiem.',
    'filled' => 'Pole jest wymagane.',
    'image' => 'Pole musi być obrazkiem.',
    'in' => 'Zaznaczone pole jest nieprawidłowe.',
    'in_array' => 'Pole nie znajduje się w :other.',
    'integer' => 'Pole musi być liczbą całkowitą.',
    'ip' => 'Pole musi być prawidłowym adresem IP.',
    'json' => 'Pole musi być poprawnym ciągiem znaków JSON.',
    'max' => [
        'numeric' => 'Wartość nie może być większa niż :max.',
        'file' => 'Pole nie może być większe niż :max kilobajtów.',
        'string' => 'Pole nie może być dłuższe niż :max znaków.',
        'array' => 'Pole nie może mieć więcej niż :max elementów.',
    ],
    'mimes' => 'Pole musi być plikiem typu :values.',
    'mimetypes' => 'Pole musi być plikiem typu :values.',
    'min' => [
        'numeric' => 'Wartość musi być nie mniejsza od :min.',
        'file' => 'Pole musi mieć przynajmniej :min kilobajtów.',
        'string' => 'Pole musi mieć przynajmniej :min znaków.',
        'array' => 'Pole musi mieć przynajmniej :min elementów.',
    ],
    'not_in' => 'Zaznaczone pole jest nieprawidłowe.',
    'numeric' => 'Pole musi być liczbą.',
    'present' => 'Pole musi być obecny.',
    'regex' => 'Format pola jest nieprawidłowy.',
    'required' => 'Pole jest wymagane.',
    'required_if' => 'Pole jest wymagane gdy :other jest :value.',
    'required_unless' => 'Pole jest wymagany jeżeli :other nie znajduje się w :values.',
    'required_with' => 'Pole jest wymagane gdy :values jest obecny.',
    'required_with_all' => 'Pole jest wymagane gdy :values jest obecny.',
    'required_without' => 'Pole jest wymagane gdy :values nie jest obecny.',
    'required_without_all' => 'Pole jest wymagane gdy żadne z :values nie są obecne.',
    'same' => 'Pole i pole :other muszą się zgadzać.',
    'size' => [
        'numeric' => 'Pole musi mieć :size.',
        'file' => 'Pole musi mieć :size kilobajtów.',
        'string' => 'Pole musi mieć :size znaków.',
        'array' => 'Pole musi zawierać :size elementów.',
    ],
    'string' => 'Pole musi być ciągiem znaków.',
    'timezone' => 'Pole musi być prawidłową strefą czasową.',
    'unique' => 'Pole musi być unikalne.',
    'uploaded' => 'Nie udało się wgrać pliku.',
    'url' => 'Format pola jest nieprawidłowy.',
    'amount_min' => 'Wartość zbyt niska',
    'registries_start_number' => 'Numer startowy dozwolony tylko dla rejestru z numeracją roczną, który nie ma wystawionych faktur',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',

        ],
        'invoice_registries' => [
            'invoice_registries_prefix' => 'Wybrano nieprawidłową wartość prefiks.',
        ],
        'addresses' => [
            'check_polish_zip_code' => 'Kod pocztowy jest za długi.',
        ],
        'items.*.quantity' => [
            'decimal_quantity' => 'Liczba musi być liczbą całkowitą.',
        ],
        'regon' => [
            'regex' => 'REGON musi składać się z 9 lub 14 cyfr.',
        ],
        'email' => [
            'blacklist' => 'Ten email jest na czarnej liście.'
        ],
        'ids' => 'Błędne identyfikatory',
        'project' => 'Podany projekt jest nieprawidłowy'
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [
        //
    ],

];
