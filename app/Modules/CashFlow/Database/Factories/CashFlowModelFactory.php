<?php

$factory->define(
    \App\Models\Db\CashFlow::class,
    function (Faker\Generator $faker) {
        return [
            'amount' => $faker->numberBetween(999, 9999),
            'receipt_id' => function ($faker) {
                return factory(\App\Models\Db\Receipt::class)->create()->id;
            },
            'description' => $faker->sentence(2),
            'flow_date' => $faker->date(),
        ];
    }
);
