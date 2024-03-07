<?php

$factory->define(
    \App\Models\Db\Invoice::class,
    function (Faker\Generator $faker) {
        return [
            'number' => $faker->unique()->slug(2),
            'order_number' => $faker->randomNumber(5),
            'invoice_registry_id' => function ($faker) {
                return factory(\App\Models\Db\InvoiceRegistry::class)->create()->id;
            },
            'drawer_id' => function ($faker) {
                return factory(\App\Models\Db\User::class)->create()->id;
            },
            'company_id' => function ($faker) {
                return factory(\App\Models\Db\Company::class)->create()->id;
            },
            'contractor_id' => function ($faker) {
                return factory(\App\Models\Db\Contractor::class)->create()->id;
            },
            'invoice_type_id' => function ($faker) {
                return  \App\Models\Db\InvoiceType::findBySlug(\App\Models\Other\InvoiceTypeStatus::VAT)->id;
            },
            'price_net' => $faker->randomNumber(4),
            'price_gross' => $faker->randomNumber(5),
            'vat_sum' => $faker->randomNumber(4),
            'gross_counted' => 0,
            'payment_left' => $faker->randomNumber(4),
            'payment_term_days' => $faker->randomNumber(4),
            'payment_method_id' => function ($faker) {
                return factory(\App\Models\Db\PaymentMethod::class)->create()->id;
            },
            'sale_date' => $faker->date(),
            'issue_date' => $faker->date(),
        ];
    }
);

$factory->define(
    \App\Models\Db\InvoiceFormat::class,
    function (Faker\Generator $faker) {
        return [
            'name' => $faker->name,
            'format' => '{%nr}/{%m}/{%Y}',
            'example' => $faker->randomNumber(4) . '/' . $faker->date('m/Y'),
        ];
    }
);

$factory->define(
    \App\Models\Db\InvoiceType::class,
    function (Faker\Generator $faker) {
        return [
            'slug' => $faker->slug(2),
            'description' => $faker->name,
        ];
    }
);

$factory->define(
    \App\Models\Db\InvoiceRegistry::class,
    function (Faker\Generator $faker) {
        return [
            'invoice_format_id' => function ($faker) {
                return factory(\App\Models\Db\InvoiceFormat::class)->create()->id;
            },
            'name' => $faker->name,
            'prefix' => $faker->unique()->lexify('???'),
            'company_id' => function ($faker) {
                return factory(\App\Models\Db\Company::class)->create()->id;
            },
            'default' => false,
        ];
    }
);

$factory->define(
    \App\Models\Db\InvoiceItem::class,
    function (Faker\Generator $faker) {
        $company_service = factory(\App\Models\Db\CompanyService::class)->create();

        return [
            'invoice_id' => function ($faker) {
                return factory(\App\Models\Db\Invoice::class)->create()->id;
            },
            'name' => $faker->name,
            'type' => \App\Models\Db\CompanyService::TYPE_SERVICE,
            'price_net' => $faker->randomNumber(6),
            'price_net_sum' => $faker->randomNumber(6),
            'price_gross' => $faker->randomNumber(6),
            'price_gross_sum' => $faker->randomNumber(6),
            'vat_sum' => $faker->randomNumber(6),
            'company_service_id' => function ($faker) use ($company_service) {
                return $company_service->id;
            },
            'pkwiu' => function ($faker) use ($company_service) {
                return $company_service->pkwiu;
            },
            'vat_rate_id' => function ($faker) {
                return factory(\App\Models\Db\VatRate::class)->create()->id;
            },
            'vat_rate' => $faker->randomElement(['23%', '8%', '0%', 'zw.', 'np. UE']),
            'quantity' => $faker->randomNumber(1),
            'base_document_id' => function ($faker) {
                return factory(\App\Models\Db\Receipt::class)->create()->id;
            },
        ];
    }
);

$factory->define(
    \App\Models\Db\InvoicePayment::class,
    function (Faker\Generator $faker) {
        return [
            'amount' => $faker->randomNumber(4),
            'invoice_id' => function ($faker) {
                return factory(\App\Models\Db\Invoice::class)->create()->id;
            },
            'payment_method_id' => function ($faker) {
                return factory(\App\Models\Db\PaymentMethod::class)->create()->id;
            },
        ];
    }
);

$factory->define(
    \App\Models\Db\InvoiceType::class,
    function (Faker\Generator $faker) {
        return [
            'slug' => $faker->unique()->word,
            'description' => $faker->unique()->word,
        ];
    }
);

$factory->define(
    \App\Models\Db\InvoiceInvoice::class,
    function (Faker\Generator $faker) {
        return [
            //
        ];
    }
);

$factory->define(
    \App\Models\Db\InvoiceReceipt::class,
    function (Faker\Generator $faker) {
        return [
            //
        ];
    }
);

$factory->define(
    \App\Models\Db\InvoiceOnlineSale::class,
    function (Faker\Generator $faker) {
        return [
            //
        ];
    }
);

$factory->define(
    \App\Models\Db\InvoiceCompany::class,
    function (Faker\Generator $faker) {
        return [
            'invoice_id' => function ($faker) {
                return factory(\App\Models\Db\Invoice::class)->create()->id;
            },
            'company_id' => function ($faker) {
                return factory(\App\Models\Db\Company::class)->create()->id;
            },
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
        ];
    }
);

$factory->define(
    \App\Models\Db\InvoiceContractor::class,
    function (Faker\Generator $faker) {
        return [
            'invoice_id' => function ($faker) {
                return factory(\App\Models\Db\Invoice::class)->create()->id;
            },
            'contractor_id' => function ($faker) {
                return factory(\App\Models\Db\Contractor::class)->create()->id;
            },
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
        ];
    }
);

$factory->define(
    \App\Models\Db\InvoiceTaxReport::class,
    function (Faker\Generator $faker) {
        return [
            'invoice_id' => function ($faker) {
                return factory(\App\Models\Db\Invoice::class)->create()->id;
            },
            'vat_rate_id' => function ($faker) {
                return factory(\App\Models\Db\VatRate::class)->create()->id;
            },
            'price_net' => $faker->randomNumber(5),
            'price_gross' => $faker->randomNumber(5),
        ];
    }
);

$factory->define(
    \App\Models\Db\InvoiceFinalAdvanceTaxReport::class,
    function (Faker\Generator $faker) {
        return [
            'invoice_id' => function ($faker) {
                return factory(\App\Models\Db\Invoice::class)->create()->id;
            },
            'vat_rate_id' => function ($faker) {
                return factory(\App\Models\Db\VatRate::class)->create()->id;
            },
            'price_net' => $faker->randomNumber(5),
            'price_gross' => $faker->randomNumber(5),
        ];
    }
);

$factory->define(
    \App\Models\Db\InvoiceInvoice::class,
    function (Faker\Generator $faker) {
        return [
            //
        ];
    }
);

$factory->define(
    \App\Models\Db\InvoiceDeliveryAddress::class,
    function (Faker\Generator $faker) {
        return [
            'invoice_id' => function ($faker) {
                return factory(\App\Models\Db\Invoice::class)->create()->id;
            },
            'street' => $faker->streetAddress,
            'number' => $faker->randomNumber(2),
            'zip_code' => $faker->numerify('##') . '-' . $faker->numerify('###'),
            'city' => $faker->city,
            'country' => $faker->country,
        ];
    }
);

$factory->define(
    \App\Models\Db\InvoiceMarginProcedure::class,
    function (Faker\Generator $faker) {
        return [
            'slug' => $faker->slug,
            'description' => $faker->name,
        ];
    }
);

$factory->define(
    \App\Models\Db\InvoiceReverseCharge::class,
    function (Faker\Generator $faker) {
        return [
            'slug' => $faker->slug,
            'description' => $faker->name,
        ];
    }
);
