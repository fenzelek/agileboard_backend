<?php

return [
    \App\Helpers\EventTypes::PROJECT_ASSIGNED => 'Do projektu :name został przypisany użytkownik :first_name :last_name.',

    \App\Helpers\EventTypes::TICKET_STORE => 'Zadanie [:title] ":name" zostało utworzone przez :current_user_first_name :current_user_last_name.',
    \App\Helpers\EventTypes::TICKET_DELETE => 'Zadanie [:title] ":name" zostało usunięte przez :current_user_first_name :current_user_last_name.',
    \App\Helpers\EventTypes::TICKET_ASSIGNED => 'Zadanie [:title] ":name" zostało przypisane do :first_name :last_name przez :current_user_first_name :current_user_last_name.',
    \App\Helpers\EventTypes::TICKET_MOVE => 'Zadanie [:title] ":name" zostało przeniesione do kolumny :status_name przez :current_user_first_name :current_user_last_name.',
    \App\Helpers\EventTypes::TICKET_EXPIRED_SCHEDULED_DATE => 'W zadaniu [:title] ":name" został przekroczony zaplanowany dzień zakończenia prac.',
    \App\Helpers\EventTypes::TICKET_TODAY_SCHEDULED_DATE => 'Zadanie [:title] ":name" dziś ma zaplanowany dzień zakończenia prac.',

    \App\Helpers\EventTypes::TICKET_COMMENT_STORE => ':first_name :last_name dodał komentarz: :comment',

    'daily_ticket_report' => [
        'subject' => 'Raport dzienny zadań',
        'welcome' => 'Cześć :name',
        'report_date' => 'Raport zadań z dnia :date',
        'th' => [
            'task' => 'Zadanie',
            'user' => 'Użytkownik',
            'field' => 'Zmiana',
            'prev_value' => 'Poprzednia wartość',
            'new_value' => 'Nowa wartość',
            'date' => 'Godzina',
        ],
    ],
];
