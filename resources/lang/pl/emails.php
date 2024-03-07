<?php

return [

    /* DEFAULT */
    'default' => [
        'greeting' => 'Cześć',
        'regards' => 'Zespół ' . config('app.name'),
        'dictionary_regards' => 'Pozdrowienia',
        'whooops' => 'Uuups!',
        'hi' => 'Cześć!',
        'trouble_with_button_1' => 'Jeżeli przycisk ',
        'trouble_with_button_2' => 'nie działa, skopiuj poniższy URL i wklej do paska adresu przeglądarki:',
        'reserved' => 'Wszelkie prawa zastrzeżone',
    ],

    /* PASSWORD RESET */
    'reset_password' => [
        'subject' => 'Zmiana hasła dla ' . config('app.name'),
        'line_1' => 'Otrzymałeś ten email, ponieważ zostało wysłane żądanie zmiany hasła z tego konta.',
        'line_2' => 'Jeżeli nie Ty wysłałeś żądanie, nie wykonuj żadnej akcji.',
        'action' => 'Zmień hasło',
    ],

    /* NEW USER INVITATION CREATED */
    'new_user_invitation_created' => [
        'subject' => 'Zaproszenie od ',
        'line_1' => 'Otrzymałeś nowe zaproszenie od firmy ',
        'line_2' => 'Twoje zaproszenie wygaśnie ',
        'action' => 'Akceptuj zaproszenie',
    ],

    /* ACTIVATION */
    'activation' => [
        'subject' => 'Aktywacja konta użytkownika w systemie ' . config('app.name'),
        'line_1' => 'Otrzymałeś ten email, ponieważ wymagana jest aktywacja Twojego konta.',
        'line_2' => 'Jeżeli nie Ty zakładałeś konto, nie wykonuj żadnej akcji.',
        'action' => 'Aktywuj konto',
    ],

    /* PAYMENT STATUS INFO */
    'payment_status_info_completed' => [
        'subject' => 'Płatność zakończona powodzeniem - ' . config('app.name'),
        'line_1' => 'Twoja płatność o numerze :number została zakończona powodzeniem.',
    ],
    'payment_status_info_canceled' => [
        'subject' => 'Płatność anulowana - ' . config('app.name'),
        'line_1' => 'Twoja płatność o numerze :number została została anulowana przez ciebie lub przez bank.',
    ],

    /*SUBSCRIPTION CANCELED*/
    'payment_subscription_canceled' => [
        'subject' => 'Subskrypcja anulowana - ' . config('app.name'),
        'line_1' => 'Co najmniej jedna z Twoich subskrypcji została anulowana, gdyż nie udało nam się automatycznie pobrać płatności. Jeśli chcesz ponownie korzystać z płatnej wersji, wejdź do zarządząnia firmą i zamów odpowiednie pakiety i moduły.',
    ],

    /* RENEW SUBSCRIPTION INFORMATION */
    'renew_subscription_information' => [
        'subject' => 'Odnowienie subskrypcji - ' . config('app.name'),
        'line_1_14' => 'Informujemy, że jedna z Twoich subskrypcji zostanie odnowiona za 14 dni. Jeśli chcesz z niej zrezygnować, wejdź do zarządząnia firmą i zmień ustawienia pakietów/modułów.',
        'line_1_1' => 'Informujemy, że jedna z Twoich subskrypcji zostanie odnowiuona w ciągu najbliższych 24 godzin. Jeśli chcesz z niej zrezygnować, wejdź do zarządząnia firmą i zmień ustawienia pakietów/modułów.',
    ],

    /* REMIND EXPIRING */
    'remind_expiring_package' => [
        'subject' => 'Twoj pakiet wygasa za :days dni - ' . config('app.name'),
        'subject_1' => 'Twoj pakiet wygasa za 1 dzień - ' . config('app.name'),
        'line_1' => 'Informujemy, że Twój aktualny pakiet wygaśnie za :days dni. Jeśli chcesz dalej z niego korzystać, wejdź do zarządząnia firmą i dokonaj płatności.',
        'line_1_1' => 'Informujemy, że Twój aktualny pakiet wygaśnie w ciągu 24h. Jeśli chcesz dalej z niego korzystać, wejdź do zarządząnia firmą i dokonaj płatności.',
    ],
    'remind_expiring_module' => [
        'subject' => 'Twoj moduł wygasa za :days dni - ' . config('app.name'),
        'subject_1' => 'Twoj moduł wygasa za 1 dzień - ' . config('app.name'),
        'line_1' => 'Informujemy, że jeden z Twoich aktywnych modułów wygaśnie za :days dni. Jeśli chcesz dalej z niego korzystać, wejdź do zarządząnia firmą i dokonaj płatności.',
        'line_1_1' => 'Informujemy, że jeden z Twoich aktywnych modułów wygaśnie za 24h. Jeśli chcesz dalej z niego korzystać, wejdź do zarządząnia firmą i dokonaj płatności.',
    ],

    /* OVERTIME ADDED */
    'overtime_information' => [
        'subject' => 'Pracownik dodał godziny nadliczbowe',
        'line_1' => 'Jeden lub więcej okresow zostało dodanych przez: ',
        'line_2' => 'Sprawdź tablicę AB',
    ],
];
