<?php

$factory->define(
    \App\Models\Db\Sprint::class,
    function (Faker\Generator $faker) {
        return [
            'name' => $faker->name,
            'project_id' => $faker->numberBetween(),
            'status' => \App\Models\Db\Sprint::INACTIVE,
        ];
    }
);

$factory->define(
    \App\Models\Db\Status::class,
    function (Faker\Generator $faker) {
        return [
            'name' => $faker->name,
            'project_id' => $faker->numberBetween(),
        ];
    }
);

$factory->define(
    \App\Models\Db\Ticket::class,
    function (Faker\Generator $faker) {
        return [
            'project_id' => $faker->numberBetween(),
            'status_id' => $faker->numberBetween(),
            'sprint_id' => $faker->numberBetween(),
            'name' => $faker->name,
            'title' => $faker->unique()->name,
            'type_id' => $faker->numberBetween(0, 1),
            'assigned_id' => 0,
            'reporter_id' => 0,
            'description' => $faker->name,
            'estimate_time' => $faker->numberBetween(),
            'scheduled_time_start' => $faker->dateTime(),
            'scheduled_time_end' => $faker->dateTime(),
        ];
    }
);

$factory->define(
    \App\Models\Db\TicketType::class,
    function (Faker\Generator $faker) {
        return [
            'name' => $faker->name,
        ];
    }
);

$factory->define(
    \App\Models\Db\TicketComment::class,
    function (Faker\Generator $faker) {
        return [
            'ticket_id' => $faker->numberBetween(),
            'user_id' => $faker->numberBetween(),
            'text' => $faker->text,
        ];
    }
);

$factory->define(
    \App\Models\Db\History::class,
    function (Faker\Generator $faker) {
        return [
            'user_id' => $faker->numberBetween(),
            'resource_id' => $faker->text,
            'object_id' => $faker->numberBetween(),
            'field_id' => $faker->numberBetween(),
            'value_before' => $faker->text,
            'label_before' => $faker->text,
            'value_after' => $faker->text,
            'label_after' => $faker->text,
        ];
    }
);

$factory->define(
    \App\Models\Db\TicketRealization::class,
    function (Faker\Generator $faker) {
        return [
            'ticket_id' => $faker->numberBetween(),
            'user_id' => $faker->numberBetween(),
            'start_at' => $faker->dateTime(),
            'end_at' => $faker->dateTime(),
        ];
    }
);
