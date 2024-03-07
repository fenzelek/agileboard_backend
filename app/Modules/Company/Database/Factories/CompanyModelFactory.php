<?php

use App\Models\Db\Role;
use App\Models\Other\RoleType;

$factory->define(
    \App\Models\Db\Company::class,
    function (Faker\Generator $faker) {
        return [
            'name' => $faker->company,
            'vatin' => $faker->numerify('##########'),
            'email' => $faker->email,
            'phone' => $faker->numerify('#########'),
            'force_calendar_to_complete' => $faker->boolean,
            'enable_calendar' => $faker->boolean,
            'enable_activity' => $faker->boolean,
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
        ];
    }
);

$factory->define(
    \App\Models\Db\Invitation::class,
    function (Faker\Generator $faker) {
        return [
            'unique_hash' => $faker->unique()->text(),
            'email' => $faker->email,
            'inviting_user_id' => $faker->randomNumber(),
            'company_id' => function ($faker) {
                return factory(\App\Models\Db\Company::class)->create()->id;
            },
            'expiration_time' => $faker->dateTime,
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
            'role_id' => $faker->randomElement(Role::pluck('id')->all()),
            'status' => \App\Models\Other\InvitationStatus::PENDING,
        ];
    }
);

$factory->define(
    \App\Models\Db\CompanyService::class,
    function (Faker\Generator $faker) {
        return [
            'name' => $faker->company,
            'type' => \App\Models\Db\CompanyService::TYPE_SERVICE,
            'pkwiu' => "{$faker->randomNumber(2)}.{$faker->randomNumber(2)}.{$faker->randomNumber(2)}.{$faker->randomNumber(1)}",
            'price_net' => $faker->randomNumber(5),
            'price_gross' => $faker->randomNumber(5),
            'vat_rate_id' => function ($faker) {
                return factory(\App\Models\Db\VatRate::class)->create()->id;
            },
            'print_on_invoice' => false,
        ];
    }
);

$factory->define(
    \App\Models\Db\CompanyToken::class,
    function (Faker\Generator $faker) {
        return [
            'company_id' => $faker->randomNumber(5),
            'user_id' => $faker->randomNumber(4),
            'role_id' => $faker->randomElement(Role::whereIn('name', [RoleType::API_USER, RoleType::API_COMPANY])->pluck('id')->all()),
            'token' => str_random(mt_rand(200, 255)),
            'domain' => $faker->safeEmailDomain,
            'ip_from' => $faker->ipv4,
            'ip_to' => $faker->ipv4,
            'ttl' => $faker->randomNumber(2),
        ];
    }
);

$factory->define(
    \App\Models\Db\Contractor::class,
    function (Faker\Generator $faker) {
        return [
            'name' => $faker->name,
        ];
    }
);

$factory->define(
    \App\Models\Db\GusCompany::class,
    function (Faker\Generator $faker) {
        return [
            'name' => $faker->company,
            'vatin' => $faker->numerify('##########'),
            'email' => $faker->email,
            'website' => $faker->domainName,
            'phone' => $faker->numerify('#########'),
            'main_address_street' => $faker->streetAddress,
            'main_address_number' => $faker->randomNumber(2),
            'main_address_zip_code' => $faker->numerify('##') . '-' . $faker->numerify('###'),
            'main_address_city' => $faker->city,
            'main_address_country' => $faker->country,
        ];
    }
);

$factory->define(
    \App\Models\Db\Payment::class,
    function (Faker\Generator $faker) {
        return [
            'price_total' => $faker->numberBetween(0, 20000),
            'vat' => 23,
            'currency' => 'PLN',
            'external_order_id' => $faker->randomAscii,
            'status' => \App\Models\Other\PaymentStatus::STATUS_BEFORE_START,
            'transaction_id' => function ($faker) {
                return factory(\App\Models\Db\Transaction::class)->create()->id;
            },
        ];
    }
);
$factory->define(
    \App\Models\Db\BankAccount::class,
    function (Faker\Generator $faker) {
        return [
            'bank_name' => $faker->name,
            'number' => $faker->numerify('########################'),
            'default' => $faker->boolean,
            'company_id' => function () {
                return factory(\App\Models\Db\Company::class)->create()->id;
            },
        ];
    }
);
$factory->define(\App\Models\Db\Module::class, function (\Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'slug' => $faker->slug,
        'description' => $faker->text,
        'visible' => 1,
        'available' => 1,
    ];
});

$factory->define(
    \App\Models\Db\Transaction::class,
    function (Faker\Generator $faker) {
        return [];
    }
);

$factory->define(
    \App\Models\Db\CompanyModuleHistory::class,
    function (Faker\Generator $faker) {
        return [
            'company_id' => $faker->numberBetween(0, 20000),
            'module_id' => $faker->numberBetween(0, 20000),
            'module_mod_id' => $faker->numberBetween(0, 20000),
            'currency' => 'PLN',
            'transaction_id' => function ($faker) {
                return factory(\App\Models\Db\Transaction::class)->create()->id;
            },
        ];
    }
);

$factory->define(
    \App\Models\Db\CompanyModule::class,
    function (Faker\Generator $faker) {
        return [
            'company_id' => $faker->numberBetween(0, 20000),
            'module_id' => $faker->numberBetween(0, 20000),
            'value' => '1',
        ];
    }
);

$factory->define(
    \App\Models\Db\ModuleMod::class,
    function (Faker\Generator $faker) {
        return [
            'module_id' => $faker->numberBetween(0, 20000),
            'test' => false,
            'value' => 0,
        ];
    }
);

$factory->define(
    \App\Models\Db\ModPrice::class,
    function (Faker\Generator $faker) {
        return [
            'module_mod_id' => $faker->numberBetween(0, 20000),
            'package_id' => $faker->numberBetween(0, 20000),
            'days' => $faker->numberBetween(30, 365),
            'price' => $faker->numberBetween(0, 20000),
            'currency' => 'PLN',
        ];
    }
);

$factory->define(
    \App\Models\Db\Subscription::class,
    function (Faker\Generator $faker) {
        return [
            'days' => $faker->numberBetween(30, 365),
            'card_token' => encrypt($faker->randomAscii),
            'user_id' => $faker->randomDigit,
            'active' => true,
        ];
    }
);

$factory->define(
    \App\Models\Db\Clipboard::class,
    function (Faker\Generator $faker) {
        return [
            'file_name' => $faker->name,
            'company_id' => function () {
                return factory(\App\Models\Db\Company::class)->create()->id;
            },
        ];
    }
);

$factory->define(\App\Models\Db\PackageModule::class, function (Faker\Generator $faker) {
    return [];
});

$factory->define(
    \App\Models\Db\Package::class,
    function (Faker\Generator $faker) {
        return [
            'slug' => $faker->slug,
            'name' => $faker->name,
            'default' => 0,
            'portal_name' => $faker->slug,
        ];
    }
);
