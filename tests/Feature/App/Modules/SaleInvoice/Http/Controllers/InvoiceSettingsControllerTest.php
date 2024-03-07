<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers;

use App\Helpers\ErrorCode;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceFormat;
use App\Models\Db\InvoiceRegistry;
use App\Models\Other\RoleType;
use App\Models\Db\Company;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class InvoiceSettingsControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * Show
     * This test is for checking API response structure.
     */
    public function test_current_invoice_format_data_structure()
    {
        Company::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        factory(InvoiceRegistry::class)->create([
            'invoice_format_id' => $company->id,
            'name' => $company->name,
            'company_id' => $company->id,
        ]);
        factory(InvoiceFormat::class, 2)->create();

        $this->get('/companies/invoice-settings?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [
                    'default_payment_term_days',
                    'invoice_registries' => [
                        'data',
                    ],
                    'default_invoice_gross_counted',
                    'vat_payer',
                ],
            ])->isJson();
    }

    /**
     * Show
     * This test is for checking API response data.
     */
    public function test_current_invoice_format_with_correct_data()
    {
        Company::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice_format = factory(InvoiceFormat::class)->create();
        $invoice_factory = factory(InvoiceRegistry::class)->create([
            'invoice_format_id' => $invoice_format->id,
            'name' => $company->name,
            'company_id' => $company->id,
        ]);
        $this->get('/companies/invoice-settings?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];
        $company = $company->fresh();
        $this->assertSame($company->default_payment_term_days, $data['default_payment_term_days']);
        $this->assertSame(
            $invoice_factory->invoice_format_id,
            $data['invoice_registries']['data'][0]['invoice_format_id']
        );
        $this->assertSame(
            $company->default_invoice_gross_counted,
            $data['default_invoice_gross_counted']
        );
        $this->assertTrue($data['vat_payer']);
    }

    /**
     * Show
     * This test is for checking API response data.
     */
    public function test_current_invoice_settings_for_no_vat_payer()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $company->vat_payer = false;
        $company->save();
        auth()->loginUsingId($this->user->id);

        $this->get('/companies/invoice-settings?selected_company_id=' . $company->id);

        $this->assertFalse($this->decodeResponseJson()['data']['vat_payer']);
    }

    /**
     * Show
     * This test is for checking result when invoice format don't have registries.
     */
    public function test_current_invoice_without_registries()
    {
        Company::whereRaw('1 = 1')->delete();
        InvoiceRegistry::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->get('/companies/invoice-settings?selected_company_id=' . $company->id)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $company = $company->fresh();
        $this->assertSame($company->default_payment_term_days, $data['default_payment_term_days']);
        $this->assertSame(
            $company->default_invoice_gross_counted,
            $data['default_invoice_gross_counted']
        );
        $this->assertEmpty($data['invoice_registries']['data']);
    }

    /** @test */
    public function show_current_invoice_with_two_registries()
    {
        Company::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice_formats = factory(InvoiceFormat::class, 2)->create();
        $registries = factory(InvoiceRegistry::class, 2)->create([
            'invoice_format_id' => $invoice_formats[0]->id,
            'name' => $company->name,
            'company_id' => $company->id,
        ]);
        $registries[1]->invoice_format_id = $invoice_formats[1]->id;
        $registries[1]->default = true;
        $registries[1]->start_number = 134;
        $registries[1]->save();

        $this->get('/companies/invoice-settings?selected_company_id=' . $company->id)
            ->isJson();

        $company = $company->fresh();
        $data = $this->response->getData()->data;

        $this->assertSame($data->default_payment_term_days, $company->default_payment_term_days);
        $this->assertSame(
            $data->default_invoice_gross_counted,
            $company->default_invoice_gross_counted
        );
        foreach ($data->invoice_registries->data as $key => $registry) {
            $this->assertEquals($registries[$key]->id, $registry->id);
            $this->assertEquals($invoice_formats[$key]->id, $registry->invoice_format_id);
            $this->assertEquals($company->name, $registry->name);
            $this->assertEquals($company->id, $registry->company_id);
            $this->assertEquals($key, $registry->default);
            $this->assertEquals($registries[$key]->start_number, $registry->start_number);
        }
    }

    /**
     * Update
     * This test is for checking API validation without data.
     */
    public function test_update_validation_without_data()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id)
            ->seeStatusCode(422);

        $this->verifyValidationResponse([
            'default_payment_term_days',
            'default_invoice_gross_counted',
            'invoice_registries',
        ]);
    }

    /**
     * Update
     * This test is for checking API validation invalid - invoice_registries.
     */
    public function test_update_validation_with_invalid_registries_id()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 4,
            'invoice_registries' => 'abc',
        ])->seeStatusCode(422);

        $this->verifyValidationResponse([
            'invoice_registries',
        ], [
            'default_payment_term_days',
        ]);
    }

    /**
     * Update
     * This test is for checking API validation invalid - default_payment_term_days.
     */
    public function test_update_validation_with_invalid_default_payment_term_days()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $invoice_format = factory(InvoiceFormat::class)->create();

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 'abc',
            'invoice_format_id' => $invoice_format->id,
        ])->seeStatusCode(422);

        $this->verifyValidationResponse(['default_payment_term_days'], ['invoice_format_id']);
    }

    /**
     * Update
     * This test is for checking API validation invalid - default_invoice_gross_counted.
     */
    public function test_update_validation_with_invalid_default_invoice_gross_counted()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);
        $invoice_format = factory(InvoiceFormat::class)->create();

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 4,
            'invoice_format_id' => $invoice_format->id,
            'default_invoice_gross_counted' => 'no_valid_boolean',
        ])->seeStatusCode(422);

        $this->verifyValidationResponse([
            'default_invoice_gross_counted',
        ], [
            'default_payment_term_days',
            'invoice_format_id',
        ]);
    }

    /**
     * Update
     * This test is for checking API validation invalid - default_invoice_gross_counted.
     */
    public function test_update_blocked_changing_gross_counted_because_if_issuing_invoices()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $company->default_payment_term_days = 24;
        $company->vat_payer = 0;
        $company->save();
        auth()->loginUsingId($this->user->id);
        $invoice_format = factory(InvoiceFormat::class)->create();
        $registry_data = [
            [
                'name' => 'New name',
                'prefix' => 'ABC',
                'default' => true,
                'invoice_format_id' => $invoice_format->id,
                'start_number' => null,
            ],
        ];

        factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 8,
            'invoice_registries' => $registry_data,
            'default_invoice_gross_counted' => 0,
        ]);

        $this->verifyErrorResponse(421, ErrorCode::COMPANY_BLOCKED_CHANGING_GROSS_COUNTED_SETTING);
    }

    /**
     * Update
     * This test is for checking API update data.
     */
    public function test_update_data_for_new_registry()
    {
        Company::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $company->default_payment_term_days = 24;
        $company->save();
        auth()->loginUsingId($this->user->id);

        $invoice_format = factory(InvoiceFormat::class)->create();

        $invoice_registry_count = InvoiceRegistry::count();

        $registry_data = [
            [
                'id' => null,
                'name' => 'New name',
                'prefix' => 'ABC',
                'default' => true,
                'invoice_format_id' => $invoice_format->id,
                'start_number' => null,
            ],
        ];

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 8,
            'invoice_registries' => $registry_data,
            'default_invoice_gross_counted' => 1,
        ])->seeStatusCode(200)->isJson();

        $this->assertEquals($invoice_registry_count + 1, InvoiceRegistry::count());

        $company_fresh = $company->fresh();
        $invoice_registry_latest = InvoiceRegistry::latest('id')->first();

        $this->assertSame($invoice_format->id, $invoice_registry_latest->invoice_format_id);
        $this->assertSame('New name', $invoice_registry_latest->name);
        $this->assertSame('ABC', $invoice_registry_latest->prefix);
        $this->assertSame(8, $company_fresh->default_payment_term_days);
        $this->assertSame(1, $company_fresh->default_invoice_gross_counted);
        $this->assertSame($company->id, $invoice_registry_latest->company_id);
        $this->assertSame($this->user->id, $invoice_registry_latest->creator_id);
        $this->assertSame($this->user->id, $invoice_registry_latest->editor_id);
    }

    /** @test */
    public function update_it_change_registry_name_prefix_and_format()
    {
        Company::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice_formats = factory(InvoiceFormat::class, 2)->create();
        $registries = factory(InvoiceRegistry::class, 2)->create([
            'invoice_format_id' => $invoice_formats[0]->id,
            'name' => 'Old name',
            'company_id' => $company->id,
            'default' => false,
            'creator_id' => 0,
            'editor_id' => 0,
        ]);

        $invoice_registry_count = InvoiceRegistry::count();

        $registries_data = [
            [
                'id' => $registries[0]->id,
                'name' => 'New name',
                'prefix' => 'ABC',
                'default' => true,
                'invoice_format_id' => $invoice_formats[1]->id,
                'start_number' => null,
            ],
            [
                'id' => $registries[1]->id,
                'name' => 'Old name',
                'prefix' => '',
                'default' => false,
                'invoice_format_id' => $invoice_formats[0]->id,
                'start_number' => null,
            ],
        ];

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 8,
            'default_invoice_gross_counted' => 1,
            'invoice_registries' => $registries_data,
        ])->seeStatusCode(200)->isJson();

        $this->assertEquals($invoice_registry_count, InvoiceRegistry::count());
        $fresh_changed_registry = $registries[0]->fresh();
        $this->assertEquals($invoice_formats[1]->id, $fresh_changed_registry->invoice_format_id);
        $this->assertEquals('New name', $fresh_changed_registry->name);
        $this->assertEquals('ABC', $fresh_changed_registry->prefix);
        $this->assertEquals($company->id, $fresh_changed_registry->company_id);
        $this->assertEquals(1, $fresh_changed_registry->default);
        $this->assertEquals(0, $fresh_changed_registry->creator_id);
        $this->assertEquals($this->user->id, $fresh_changed_registry->editor_id);
        $this->assertEquals('Old name', $registries[1]->fresh()->name);
        $this->assertEquals($registries[1]->prefix, $registries[1]->prefix);
        $this->assertEquals(0, $registries[1]->fresh()->default);
        $this->assertEquals($invoice_formats[0]->id, $registries[1]->fresh()->invoice_format_id);
    }

    /** @test */
    public function update_it_cant_change_registry_prefix_or_format()
    {
        Company::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice_formats = factory(InvoiceFormat::class, 2)->create();
        $registries = factory(InvoiceRegistry::class, 2)->create([
            'invoice_format_id' => $invoice_formats[0]->id,
            'name' => 'Old name',
            'company_id' => $company->id,
            'default' => false,
            'creator_id' => 0,
            'editor_id' => 0,
        ]);
        $registries[0]->is_used = true;
        $registries[0]->save();

        $invoice_registry_count = InvoiceRegistry::count();

        $registries_data = [
            [
                'id' => $registries[0]->id,
                'name' => 'New name',
                'prefix' => 'ABC',
                'default' => true,
                'invoice_format_id' => $invoice_formats[1]->id,
                'start_number' => null,
            ],
            [
                'id' => $registries[1]->id,
                'name' => 'Old name',
                'prefix' => '',
                'default' => false,
                'invoice_format_id' => $invoice_formats[0]->id,
                'start_number' => null,
            ],
        ];

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 8,
            'default_invoice_gross_counted' => 1,
            'invoice_registries' => $registries_data,
        ])->seeStatusCode(200)->isJson();

        $this->assertEquals($invoice_registry_count, InvoiceRegistry::count());
        $fresh_changed_registry = $registries[0]->fresh();
        $this->assertEquals($invoice_formats[0]->id, $fresh_changed_registry->invoice_format_id);
        $this->assertEquals($registries[0]->prefix, $fresh_changed_registry->prefix);
    }

    /** @test */
    public function update_only_one_registry_can_be_set_as_default()
    {
        Company::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice_format = factory(InvoiceFormat::class)->create();
        $registries = factory(InvoiceRegistry::class, 2)->create([
            'invoice_format_id' => $invoice_format->id,
            'name' => 'Old name',
            'company_id' => $company->id,
            'default' => false,
            'creator_id' => 0,
            'editor_id' => 0,
        ]);

        $invoice_registry_count = InvoiceRegistry::count();

        $registries_data = [
            [
                'id' => $registries[0]->id,
                'name' => 'New name',
                'prefix' => '',
                'default' => true,
                'invoice_format_id' => $invoice_format->id,
                'start_number' => null,
            ],
            [
                'id' => $registries[1]->id,
                'name' => 'Old name',
                'prefix' => '',
                'default' => true,
                'invoice_format_id' => $invoice_format->id,
                'start_number' => null,
            ],
        ];

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 8,
            'default_invoice_gross_counted' => 1,
            'invoice_registries' => $registries_data,
        ])->seeStatusCode(422)->isJson();

        $this->verifyValidationResponse([
            'one_default_registry',
        ]);
    }

    /** @test */
    public function update_it_add_new_delete_and_set_as_default_registries()
    {
        Company::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice_format = factory(InvoiceFormat::class)->create();
        $registries = factory(InvoiceRegistry::class, 3)->create([
            'invoice_format_id' => $invoice_format->id,
            'name' => 'Old name',
            'company_id' => $company->id,
            'default' => false,
            'creator_id' => 0,
            'editor_id' => 0,
        ]);

        $this->assertEquals(3, InvoiceRegistry::count());

        $registries_data = [
            [
                'id' => null,
                'name' => ' New registry ',
                'prefix' => ' ABC ',
                'default' => false,
                'invoice_format_id' => $invoice_format->id,
                'start_number' => null,
            ],
            [
                'id' => $registries[0]->id,
                'name' => 'New name',
                'prefix' => '',
                'default' => true,
                'invoice_format_id' => $invoice_format->id,
                'start_number' => null,
            ],
        ];

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 8,
            'default_invoice_gross_counted' => 1,
            'invoice_registries' => $registries_data,
        ])->seeStatusCode(200)->isJson();

        $new_registries = InvoiceRegistry::orderBy('id')->get();
        $this->assertEquals(2, InvoiceRegistry::count());

        $this->assertEquals($invoice_format->id, $new_registries[0]->invoice_format_id);
        $this->assertEquals('New name', $new_registries[0]->name);
        $this->assertEquals('', $new_registries[0]->prefix);
        $this->assertEquals($company->id, $new_registries[0]->company_id);
        $this->assertEquals(1, $new_registries[0]->default);
        $this->assertEquals(0, $new_registries[0]->creator_id);
        $this->assertEquals($this->user->id, $new_registries[0]->editor_id);
        $this->assertEquals('New registry', $new_registries[1]->name);
        $this->assertEquals('ABC', $new_registries[1]->prefix);
        $this->assertEquals(0, $new_registries[1]->default);
        $this->assertEquals($invoice_format->id, $new_registries[1]->invoice_format_id);
    }

    /** @test */
    public function update_it_cant_delete_registry_with_invoice_attached()
    {
        Company::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice_format = factory(InvoiceFormat::class)->create();
        $registries = factory(InvoiceRegistry::class, 2)->create([
            'invoice_format_id' => $invoice_format->id,
            'name' => 'Old name',
            'company_id' => $company->id,
            'default' => false,
            'creator_id' => 0,
            'editor_id' => 0,
        ]);
        $registries[1]->is_used = true;
        $registries[1]->save();

        $this->assertEquals(2, InvoiceRegistry::count());

        $registries_data = [
            [
                'id' => null,
                'name' => 'New registry',
                'prefix' => 'ABC',
                'default' => false,
                'invoice_format_id' => $invoice_format->id,
                'start_number' => null,
            ],
            [
                'id' => $registries[0]->id,
                'name' => 'New name',
                'prefix' => '',
                'default' => true,
                'invoice_format_id' => $invoice_format->id,
                'start_number' => null,
            ],
        ];

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 8,
            'default_invoice_gross_counted' => 1,
            'invoice_registries' => $registries_data,
        ])->seeStatusCode(200)->isJson();

        $new_registries = InvoiceRegistry::orderBy('id')->get();
        $this->assertEquals(3, InvoiceRegistry::count());

        $this->assertEquals($invoice_format->id, $new_registries[0]->invoice_format_id);
        $this->assertEquals('New name', $new_registries[0]->name);
        $this->assertEquals('', $new_registries[0]->prefix);
        $this->assertEquals($company->id, $new_registries[0]->company_id);
        $this->assertEquals(1, $new_registries[0]->default);
        $this->assertEquals(0, $new_registries[0]->creator_id);
        $this->assertEquals($this->user->id, $new_registries[0]->editor_id);
        $this->assertEquals('Old name', $new_registries[1]->name);
        $this->assertEquals($registries[1]->prefix, $new_registries[1]->prefix);
        $this->assertEquals(0, $new_registries[1]->default);
        $this->assertEquals($invoice_format->id, $new_registries[1]->invoice_format_id);
        $this->assertEquals('New registry', $new_registries[2]->name);
        $this->assertEquals('ABC', $new_registries[2]->prefix);
        $this->assertEquals(0, $new_registries[2]->default);
        $this->assertEquals($invoice_format->id, $new_registries[2]->invoice_format_id);
    }

    /** @test */
    public function update_duplicated_prefix_will_throw_validation_error()
    {
        Company::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice_format = factory(InvoiceFormat::class)->create();
        $registry = factory(InvoiceRegistry::class)->create([
            'invoice_format_id' => $invoice_format->id,
            'name' => 'Old name',
            'prefix' => 'ABC',
            'company_id' => $company->id,
            'default' => false,
            'is_used' => 1,
            'creator_id' => 0,
            'editor_id' => 0,
        ]);

        $this->assertEquals(1, InvoiceRegistry::count());

        $registries_data = [
            [
                'id' => null,
                'name' => 'New name',
                'prefix' => ' ABC ',
                'default' => true,
                'invoice_format_id' => $invoice_format->id,
                'start_number' => null,
            ],
        ];

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 8,
            'default_invoice_gross_counted' => 1,
            'invoice_registries' => $registries_data,
        ])->seeStatusCode(422)->isJson();

        $this->verifyValidationResponse(['invoice_registries']);
    }

    /** @test */
    public function update_check_if_validation_ignore_same_prefix_for_same_id()
    {
        Company::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice_format = factory(InvoiceFormat::class)->create();
        $registry = factory(InvoiceRegistry::class)->create([
            'invoice_format_id' => $invoice_format->id,
            'name' => 'Old name',
            'prefix' => 'ABC',
            'company_id' => $company->id,
            'default' => true,
            'creator_id' => 0,
            'editor_id' => 0,
        ]);

        $this->assertEquals(1, InvoiceRegistry::count());

        $registries_data = [
            [
                'id' => $registry->id,
                'name' => 'New name',
                'prefix' => 'ABC',
                'default' => true,
                'invoice_format_id' => $invoice_format->id,
                'start_number' => null,
            ],
        ];

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 8,
            'default_invoice_gross_counted' => 1,
            'invoice_registries' => $registries_data,
        ])->seeStatusCode(200)->isJson();

        $new_registries = InvoiceRegistry::orderBy('id')->get();
        $this->assertEquals(1, InvoiceRegistry::count());

        $this->assertEquals($invoice_format->id, $new_registries[0]->invoice_format_id);
        $this->assertEquals('New name', $new_registries[0]->name);
        $this->assertEquals('ABC', $new_registries[0]->prefix);
        $this->assertEquals($company->id, $new_registries[0]->company_id);
        $this->assertEquals(1, $new_registries[0]->default);
        $this->assertEquals(0, $new_registries[0]->creator_id);
        $this->assertEquals($this->user->id, $new_registries[0]->editor_id);
    }

    /** @test */
    public function update_can_remove_and_add_new_registry_with_same_prefix()
    {
        Company::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice_format = factory(InvoiceFormat::class)->create();
        $registry = factory(InvoiceRegistry::class)->create([
            'id' => 1,
            'invoice_format_id' => $invoice_format->id,
            'name' => 'Update name',
            'prefix' => 'AA',
            'company_id' => $company->id,
            'default' => true,
            'creator_id' => 0,
            'editor_id' => 0,
        ]);
        $registry2 = factory(InvoiceRegistry::class)->create([
            'id' => 2,
            'invoice_format_id' => $invoice_format->id,
            'name' => 'Update prefix',
            'prefix' => 'BB',
            'company_id' => $company->id,
            'default' => false,
            'creator_id' => 0,
            'editor_id' => 0,
        ]);
        factory(InvoiceRegistry::class)->create([
            'id' => 3,
            'invoice_format_id' => $invoice_format->id,
            'name' => 'Remove',
            'prefix' => 'GG',
            'company_id' => $company->id,
            'default' => false,
            'creator_id' => 0,
            'editor_id' => 0,
        ]);
        factory(InvoiceRegistry::class)->create([
            'id' => 4,
            'invoice_format_id' => $invoice_format->id,
            'name' => 'Cant remove',
            'prefix' => 'HH',
            'company_id' => $company->id,
            'default' => false,
            'is_used' => 1,
            'creator_id' => 0,
            'editor_id' => 0,
        ]);
        factory(InvoiceRegistry::class)->create([
            'id' => 5,
            'invoice_format_id' => $invoice_format->id,
            'name' => 'Outside of company',
            'prefix' => 'GG',
            'company_id' => (int) $company->id + 1,
            'default' => false,
            'creator_id' => 0,
            'editor_id' => 0,
        ]);

        $this->assertEquals(5, InvoiceRegistry::count());

        $registries_data = [
            [
                'id' => $registry->id,
                'name' => 'New name Update name',
                'prefix' => 'AA',
                'default' => true,
                'invoice_format_id' => $invoice_format->id,
                'start_number' => null,
            ],
            [
                'id' => null,
                'name' => 'Newer name New',
                'prefix' => 'BB',
                'default' => false,
                'invoice_format_id' => $invoice_format->id,
                'start_number' => null,
            ],
            [
                'id' => $registry2->id,
                'name' => 'Newest name Update prefix',
                'prefix' => 'CC',
                'default' => false,
                'invoice_format_id' => $invoice_format->id,
                'start_number' => null,
            ],
        ];

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 8,
            'default_invoice_gross_counted' => 1,
            'invoice_registries' => $registries_data,
        ])->seeStatusCode(200)->isJson();

        $new_registries = InvoiceRegistry::orderBy('id')->get();
        $this->assertEquals(5, InvoiceRegistry::count());

        $this->assertEquals(1, $new_registries[0]->id);
        $this->assertEquals($invoice_format->id, $new_registries[0]->invoice_format_id);
        $this->assertEquals('New name Update name', $new_registries[0]->name);
        $this->assertEquals('AA', $new_registries[0]->prefix);
        $this->assertEquals($company->id, $new_registries[0]->company_id);
        $this->assertEquals(1, $new_registries[0]->default);
        $this->assertEquals(0, $new_registries[0]->creator_id);
        $this->assertEquals($this->user->id, $new_registries[0]->editor_id);
        $this->assertEquals(2, $new_registries[1]->id);
        $this->assertEquals('Newest name Update prefix', $new_registries[1]->name);
        $this->assertEquals('CC', $new_registries[1]->prefix);
        $this->assertEquals(4, $new_registries[2]->id);
        $this->assertEquals('Cant remove', $new_registries[2]->name);
        $this->assertEquals('HH', $new_registries[2]->prefix);
        $this->assertEquals(5, $new_registries[3]->id);
        $this->assertEquals('Outside of company', $new_registries[3]->name);
        $this->assertEquals('GG', $new_registries[3]->prefix);
        $this->assertEquals($company->id + 1, $new_registries[3]->company_id);
        $this->assertEquals('Newer name New', $new_registries[4]->name);
        $this->assertEquals('BB', $new_registries[4]->prefix);
    }

    /** @test */
    public function update_adding_registry_without_sending_id()
    {
        Company::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice_format = factory(InvoiceFormat::class)->create();
        $registry = factory(InvoiceRegistry::class)->create([
            'id' => 1,
            'invoice_format_id' => $invoice_format->id,
            'name' => 'Update name',
            'prefix' => 'AA',
            'company_id' => $company->id,
            'default' => true,
            'creator_id' => 0,
            'editor_id' => 0,
        ]);

        factory(Invoice::class)->create(['invoice_registry_id' => $registry->id]);

        $this->assertEquals(1, InvoiceRegistry::count());

        $registries_data = [
            [
                'name' => 'New registry with same prefix',
                'prefix' => 'AA',
                'default' => true,
                'invoice_format_id' => $invoice_format->id,
                'start_number' => null,
            ],
        ];

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 8,
            'default_invoice_gross_counted' => 1,
            'invoice_registries' => $registries_data,
        ])->seeStatusCode(200)->isJson();

        $new_registries = InvoiceRegistry::orderBy('id')->get();
        $this->assertEquals(1, InvoiceRegistry::count());

        $this->assertEquals($invoice_format->id, $new_registries[0]->invoice_format_id);
        $this->assertEquals('New registry with same prefix', $new_registries[0]->name);
        $this->assertEquals('AA', $new_registries[0]->prefix);
        $this->assertEquals($company->id, $new_registries[0]->company_id);
        $this->assertEquals(1, $new_registries[0]->default);
    }

    /** @test */
    public function update_cant_add_start_number_to_used_registry()
    {
        Company::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice_format = factory(InvoiceFormat::class)->create();
        $registry = factory(InvoiceRegistry::class)->create([
            'invoice_format_id' => $invoice_format->id,
            'name' => 'Old name',
            'company_id' => $company->id,
            'prefix' => '',
            'default' => false,
            'creator_id' => 0,
            'editor_id' => 0,
            'is_used' => true,
        ]);

        $this->assertEquals(1, InvoiceRegistry::count());

        $registry_data = [
            [
                'id' => $registry->id,
                'name' => 'New name',
                'prefix' => '',
                'default' => true,
                'invoice_format_id' => $invoice_format->id,
                'start_number' => 123,
            ],
        ];

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 8,
            'default_invoice_gross_counted' => 1,
            'invoice_registries' => $registry_data,
        ])->seeStatusCode(422);

        $this->verifyValidationResponse([
            'invoice_registries.0.start_number',
        ]);
    }

    /** @test */
    public function update_cant_add_start_number_to_monthly_numeration_registry()
    {
        Company::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice_format = factory(InvoiceFormat::class)->create();
        $registry = factory(InvoiceRegistry::class)->create([
            'invoice_format_id' => $invoice_format->id,
            'name' => 'Old name',
            'company_id' => $company->id,
            'prefix' => '',
            'default' => false,
            'creator_id' => 0,
            'editor_id' => 0,
        ]);

        $this->assertEquals(1, InvoiceRegistry::count());

        $registry_data = [
            [
                'id' => $registry->id,
                'name' => 'New name',
                'prefix' => '',
                'default' => true,
                'invoice_format_id' => $invoice_format->id,
                'start_number' => 123,
            ],
            [
                'id' => null,
                'name' => 'New name',
                'prefix' => 'a',
                'default' => false,
                'invoice_format_id' => $invoice_format->id,
                'start_number' => 123,
            ],
        ];

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 8,
            'default_invoice_gross_counted' => 1,
            'invoice_registries' => $registry_data,
        ])->seeStatusCode(422);

        $this->verifyValidationResponse([
            'invoice_registries.0.start_number',
            'invoice_registries.1.start_number',
        ]);
    }

    /** @test */
    public function update_add_start_number_to_registry()
    {
        Company::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $year_format = InvoiceFormat::findByFormatStrict(InvoiceFormat::YEARLY_FORMAT);
        $registry = factory(InvoiceRegistry::class)->create([
            'invoice_format_id' => $year_format->id,
            'name' => 'Old name',
            'company_id' => $company->id,
            'prefix' => '',
            'default' => false,
            'creator_id' => 0,
            'editor_id' => 0,
        ]);

        $this->assertEquals(1, InvoiceRegistry::count());

        $registry_data = [
            [
                'id' => $registry->id,
                'name' => 'New name',
                'prefix' => '',
                'default' => true,
                'invoice_format_id' => $year_format->id,
                'start_number' => 123,
            ],
            [
                'id' => null,
                'name' => 'New name',
                'prefix' => 'asd',
                'default' => false,
                'invoice_format_id' => $year_format->id,
                'start_number' => 124,
            ],
        ];

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 8,
            'default_invoice_gross_counted' => 1,
            'invoice_registries' => $registry_data,
        ])->assertResponseOk();

        $data = InvoiceRegistry::all();
        $this->assertEquals(2, $data->count());
        $this->assertEquals(123, $data[0]->start_number);
        $this->assertEquals(124, $data[1]->start_number);
    }

    /** @test */
    public function update_gets_error_for_new_month_registry_when_start_number_is_sent()
    {
        Company::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $year_format = InvoiceFormat::findByFormatStrict(InvoiceFormat::YEARLY_FORMAT);
        $month_format = InvoiceFormat::findByFormatStrict(InvoiceFormat::MONTHLY_FORMAT);
        $registry = factory(InvoiceRegistry::class)->create([
            'invoice_format_id' => $year_format->id,
            'name' => 'Old name',
            'company_id' => $company->id,
            'prefix' => '',
            'default' => false,
            'creator_id' => 0,
            'editor_id' => 0,
        ]);

        $this->assertEquals(1, InvoiceRegistry::count());

        $registry_data = [
            [
                'id' => $registry->id,
                'name' => 'New name',
                'prefix' => '',
                'default' => true,
                'invoice_format_id' => $year_format->id,
                'start_number' => 123,
            ],
            [
                // it's invalid for month format start_number should not be sent
                'name' => 'New name',
                'prefix' => 'asd',
                'default' => false,
                'invoice_format_id' => $month_format->id,
                'start_number' => 124,
            ],
            [
                'name' => 'New name 2',
                'prefix' => 'asd2',
                'default' => false,
                'invoice_format_id' => $month_format->id,
                'start_number' => null,
            ],
            [
                // it's invalid for month format start_number should not be sent
                // we send here id to make sure it won't affect validation process
                'id' => null,
                'name' => 'New name 3',
                'prefix' => 'asd3',
                'default' => false,
                'invoice_format_id' => $month_format->id,
                'start_number' => 124,
            ],
        ];

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 8,
            'default_invoice_gross_counted' => 1,
            'invoice_registries' => $registry_data,
        ]);

        $this->verifyValidationResponse([
            'invoice_registries.1.start_number',
            'invoice_registries.3.start_number',
        ], ['invoice_registries.0.start_number', 'invoice_registries.2.start_number']);

        $this->assertEquals(1, InvoiceRegistry::count());
    }

    /** @test */
    public function update_adding_registry_when_number_and_dash_and_underscore_is_used()
    {
        Company::whereRaw('1 = 1')->delete();

        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $invoice_format = factory(InvoiceFormat::class)->create();
        $registry = factory(InvoiceRegistry::class)->create([
            'id' => 1,
            'invoice_format_id' => $invoice_format->id,
            'name' => 'Update name',
            'prefix' => 'AA',
            'company_id' => $company->id,
            'default' => true,
            'creator_id' => 0,
            'editor_id' => 0,
        ]);

        factory(Invoice::class)->create(['invoice_registry_id' => $registry->id]);

        $this->assertEquals(1, InvoiceRegistry::count());

        $year_format = InvoiceFormat::findByFormatStrict(InvoiceFormat::YEARLY_FORMAT);

        $registries_data = [
            [
                'name' => 'New registry with same prefix',
                'prefix' => 'AA-123_XX',
                'default' => true,
                'invoice_format_id' => $year_format->id,
                'start_number' => 15,
            ],
        ];

        $this->put('/companies/invoice-settings?selected_company_id=' . $company->id, [
            'default_payment_term_days' => 8,
            'default_invoice_gross_counted' => 1,
            'invoice_registries' => $registries_data,
        ])->seeStatusCode(200)->isJson();

        $new_registries = InvoiceRegistry::orderBy('id')->get();
        $this->assertEquals(1, InvoiceRegistry::count());

        $this->assertEquals($year_format->id, $new_registries[0]->invoice_format_id);
        $this->assertEquals('New registry with same prefix', $new_registries[0]->name);
        $this->assertEquals('AA-123_XX', $new_registries[0]->prefix);
        $this->assertEquals($company->id, $new_registries[0]->company_id);
        $this->assertEquals(1, $new_registries[0]->default);
        $this->assertEquals(15, $new_registries[0]->start_number);
    }
}
