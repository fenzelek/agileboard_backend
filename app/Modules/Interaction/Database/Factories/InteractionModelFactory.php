<?php

use App\Models\Db\User;

$factory->define(
    \App\Models\Db\Interaction::class,
    function () {
        return [
            'user_id' => function () {
                return factory(User::class)->create()->id;
            },
            'project_id' => function () {
                return factory(\App\Models\Db\Project::class)->create()->id;
            },
            'company_id' => function () {
                return factory(\App\Models\Db\Company::class)->create()->id;
            },
            'source_type' => \App\Models\Other\Interaction\SourceType::TICKET_COMMENT,
            'source_id' => function () {
                return factory(\App\Models\Db\TicketComment::class)->create()->id;
            },
            'event_type' => \App\Models\Other\Interaction\InteractionEventType::TICKET_COMMENT_NEW,
        ];
    }
);

$factory->define(
    \App\Models\Db\InteractionPing::class,
    function (Faker\Generator $faker) {
        return [
            'interaction_id' => $faker->randomNumber(4),
            'recipient_id' => function () {
                return factory(User::class)->create()->id;
            },
            'ref' => $faker->text,
            'notifiable'=> \App\Models\Other\Interaction\NotifiableType::USER,
            'message' => $faker->text,
        ];
    }
);
