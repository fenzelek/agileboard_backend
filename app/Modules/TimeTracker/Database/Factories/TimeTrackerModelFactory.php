<?php

use App\Models\Db\User;

$factory->define(
    App\Models\Db\TimeTracker\Frame::class,
    function (Faker\Generator $faker) {
        return [
            'user_id' => function () {
                return factory(User::class)->create()->id;
            },
            'project_id' => function () {
                return factory(\App\Models\Db\Project::class)->create()->id;
            },
            'ticket_id' => function () {
                return factory(\App\Models\Db\Ticket::class)->create()->id;
            },
            'from' => now()->timestamp,
            'to' => now()->timestamp,
            'activity' => $faker->randomNumber(),

        ];
    }
);

$factory->define(
    App\Models\Db\TimeTracker\Screen::class,
    function (Faker\Generator $faker) {
        return [
            'user_id' => function () {
                return factory(User::class)->create()->id;
            },
            'name' => $faker->word(),
            'thumbnail_link' => $faker->url,
            'url_link' => $faker->url,
        ];
    }
);
