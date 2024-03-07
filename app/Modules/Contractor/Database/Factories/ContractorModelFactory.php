<?php

$factory->define(
    \App\Models\Db\Contractor::class,
    function (Faker\Generator $faker) {
        return [
            'name' => $faker->company,
            'vatin' => $faker->numerify('##########'),
            'email' => $faker->email,
            'phone' => $faker->numerify('#########'),
            'bank_name' => $faker->name,
            'bank_account_number' => $faker->numerify('########################'),
            'main_address_street' => $faker->streetAddress,
            'main_address_number' => $faker->randomNumber(2),
            'main_address_zip_code' => $faker->numerify('##') . '-' . $faker->numerify('###'),
            'main_address_city' => $faker->city,
            'main_address_country' => $faker->country,
            'contact_address_street' => $faker->streetAddress,
            'contact_address_number' => $faker->randomNumber(2),
            'contact_address_zip_code' => $faker->numerify('##') . '-' . $faker->numerify('###'),
            'contact_address_city' => $faker->city,
            'contact_address_country' => $faker->country,
            'default_payment_term_days' => 7,
            'default_payment_method_id' => 0,
            'creator_id' => 0,
            'editor_id' => 0,
            'remover_id' => 0,
        ];
    }
);

$factory->define(App\Models\Db\ContractorAddress::class, function (\Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'type' => \App\Models\Other\ContractorAddressType::DELIVERY,
        'street' => $faker->streetAddress,
        'number' => $faker->randomNumber(2),
        'zip_code' => $faker->randomNumber(5),
        'city' => $faker->city,
        'country' => $faker->country,
        'contractor_id' => function () {
            return factory(\App\Models\Db\Contractor::class)->create()->id;
        },
    ];
});
