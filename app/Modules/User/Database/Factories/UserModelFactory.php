<?php

$factory->define(\App\Models\Db\User::class, function (Faker\Generator $faker) {
    return [
        'email' => $faker->unique()->safeEmail,
        'password' => $faker->password,
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'deleted' => 0,
        'activated' => 1,
        'activate_hash' => $faker->unique()->text(),
    ];
});

$factory->define(\App\Models\Db\Role::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->unique()->name,
        'default' => $faker->boolean(),
    ];
});

$factory->define(\App\Models\Db\UserShortToken::class, function (Faker\Generator $faker) {
    return [
        'user_id' => $faker->randomNumber(6),
        'token' => str_random(mt_rand(100, 150)),
        'expires_at' => Carbon\Carbon::now()->addMinutes(2),
    ];
});
