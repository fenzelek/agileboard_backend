<?php

$factory->define(
    App\Models\SaleOther::class,
    function (Faker\Generator $faker) {
        return [
            //
        ];
    }
);

$factory->define(
    \App\Models\Db\Receipt::class,
    function (Faker\Generator $faker) {
        $number = $faker->unique()->numerify();

        return [
            'number' => $faker->unique()->numerify('ABC-####'),
            'transaction_number' => $faker->unique()->numerify('ABC######'),
            'user_id' => function ($faker) use ($number) {
                return factory(\App\Models\Db\User::class)->create([
                    'first_name' => 'Name ' . $number,
                    'last_name' => 'Surname ' . $number,
                ])->id;
            },
            'company_id' => function ($faker) {
                return factory(\App\Models\Db\Company::class)->create()->id;
            },
            'sale_date' => $faker->dateTime,
            'price_net' => $faker->randomNumber(),
            'price_gross' => $faker->randomNumber(),
            'vat_sum' => $faker->randomNumber(),
            'payment_method_id' => function ($faker) {
                return factory(\App\Models\Db\PaymentMethod::class)->create()->id;
            },
        ];
    }
);

$factory->define(
    \App\Models\Db\PaymentMethod::class,
    function (Faker\Generator $faker) {
        $slug = str_slug($faker->unique()->word);

        return [
            'slug' => $slug,
            'name' => $slug,
        ];
    }
);

$factory->define(
    \App\Models\Db\ReceiptItem::class,
    function (Faker\Generator $faker) {
        $company_service = factory(\App\Models\Db\CompanyService::class)->create();
        $vat_rate = factory(\App\Models\Db\VatRate::class)->create();

        return [
            'receipt_id' => function ($faker) {
                return factory(\App\Models\Db\Receipt::class)->create()->id;
            },
            'company_service_id' => $company_service->id,
            'name' => $company_service->name,
            'price_net' => $faker->randomNumber(3),
            'price_net_sum' => $faker->randomNumber(6),
            'price_gross' => $faker->randomNumber(3),
            'price_gross_sum' => $faker->randomNumber(6),
            'vat_rate' => $vat_rate->name,
            'vat_rate_id' => $vat_rate->id,
            'vat_sum' => $faker->randomNumber(3),
            'quantity' => $faker->randomNumber(2),
        ];
    }
);

$factory->define(
    \App\Models\Db\OnlineSale::class,
    function (Faker\Generator $faker) {
        $payment_method = factory(\App\Models\Db\PaymentMethod::class)->create();
        $number = $faker->unique()->numerify();

        return [
            'number' => $number,
            'transaction_number' => $faker->unique()->numerify(),
            'email' => $number . '@' . $faker->safeEmailDomain,
            'company_id' => function ($faker) {
                return factory(\App\Models\Db\Company::class)->create()->id;
            },
            'sale_date' => $faker->dateTime,
            'price_net' => $faker->randomNumber(),
            'price_gross' => $faker->randomNumber(),
            'vat_sum' => $faker->randomNumber(),
            'payment_method_id' => $payment_method->id,
        ];
    }
);

$factory->define(
    \App\Models\Db\OnlineSaleItem::class,
    function (Faker\Generator $faker) {
        $company_service = factory(\App\Models\Db\CompanyService::class)->create();
        $vat_rate = factory(\App\Models\Db\VatRate::class)->create();

        return [
            'online_sale_id' => function ($faker) {
                return factory(\App\Models\Db\OnlineSale::class)->create()->id;
            },
            'company_service_id' => $company_service->id,
            'name' => $company_service->name,
            'price_net' => $faker->randomNumber(3),
            'price_net_sum' => $faker->randomNumber(6),
            'price_gross' => $faker->randomNumber(3),
            'price_gross_sum' => $faker->randomNumber(6),
            'vat_rate' => $vat_rate->name,
            'vat_rate_id' => $vat_rate->id,
            'vat_sum' => $faker->randomNumber(3),
            'quantity' => $faker->randomNumber(2),
        ];
    }
);

$factory->define(
    \App\Models\Db\ErrorLog::class,
    function (Faker\Generator $faker) {
        $number = $faker->unique()->numerify();

        return [
            'company_id' => function ($faker) {
                return factory(\App\Models\Db\Company::class)->create()->id;
            },
            'user_id' => function ($faker) use ($number) {
                return factory(\App\Models\Db\User::class)->create([
                    'first_name' => 'Name ' . $number,
                    'last_name' => 'Surname ' . $number,
                ])->id;
            },
            'transaction_number' => $faker->unique()->numerify(),
            'url' => 'http://localhost:8000/receipts',
            'method' => 'POST',
            'request' => json_encode($faker->slug()),
            'status_code' => '422',
            'response' => json_encode($faker->slug()),
            'request_date' => \Carbon\Carbon::now(),
        ];
    }
);
