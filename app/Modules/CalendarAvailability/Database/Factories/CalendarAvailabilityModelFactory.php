<?php

$factory->define(
    \App\Models\Db\UserAvailability::class,
    function (Faker\Generator $faker) {
        return [
            'time_start' => $faker->time(),
            'time_stop' => $faker->time(),
            'day' => $faker->date(),
            'available' => (int) $faker->boolean(),
            'description' => $faker->text(50),
            'user_id' => $faker->randomDigitNotNull,
        ];
    }
);
