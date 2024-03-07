<?php

$factory->define(
    App\Modules\Sale\Models\Sale::class,
    function (Faker\Generator $faker) {
        return [
            //
        ];
    }
);

$factory->define(
    \App\Models\Db\VatRate::class,
    function (Faker\Generator $faker) {
        return [
            'rate' => $faker->randomNumber(),
            'name' => $faker->unique()->name,
            'is_visible' => $faker->boolean(),
        ];
    }
);

$factory->define(
    \App\Models\Db\PaymentMethod::class,
    function (Faker\Generator $faker) {
        $name = $faker->unique()->text(127);

        return [
            'slug' => str_limit(str_slug($name), 63, ''),
            'name' => $name,
        ];
    }
);
