<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController;

use App\Helpers\ErrorCode;
use App\Models\Db\BankAccount;
use App\Models\Db\CashFlow;
use App\Models\Db\ContractorAddress;
use App\Models\Db\CountryVatinPrefix;
use App\Models\Db\InvoiceDeliveryAddress;
use App\Models\Db\ServiceUnit;
use App\Models\Other\ModuleType;
use App\Models\Db\InvoicePayment as ModelInvoicePayment;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceFormat;
use App\Models\Db\InvoiceItem;
use App\Models\Db\InvoiceOnlineSale;
use App\Models\Db\InvoicePayment;
use App\Models\Other\PaymentMethodType;
use App\Models\Db\Receipt;
use App\Models\Db\OnlineSale;
use Faker\Factory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Db\Contractor;
use App\Models\Db\PaymentMethod;
use App\Models\Db\InvoiceType;
use App\Models\Db\CompanyService;
use App\Models\Db\VatRate;
use App\Models\Db\Company;
use App\Models\Db\InvoiceRegistry;
use Carbon\Carbon;
use App\Models\Db\InvoiceCompany;
use App\Models\Db\InvoiceContractor;
use App\Models\Db\InvoiceTaxReport;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Db\InvoiceReceipt;
use Illuminate\Support\Facades\Storage;
use Tests\BrowserKitTestCase;
use Tests\Feature\App\Modules\SaleInvoice\Helpers\FinancialEnvironment;
use File;

class StoreTest extends BrowserKitTestCase
{
    use DatabaseTransactions, FinancialEnvironment;

    protected $registry;

    public function setUp():void
    {
        parent::setUp();
        $this->registry = factory(InvoiceRegistry::class)->create();
    }

    /** @test */
    public function store_user_has_permission()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->post('invoices?selected_company_id=' . $company->id)
            ->assertResponseStatus(422);
    }

    /** @test */
    public function store_it_returns_validation_error_without_data()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->post('invoices?selected_company_id=' . $company->id)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'sale_date',
            'issue_date',
            'invoice_registry_id',
            'payment_term_days',
            'contractor_id',
            'payment_method_id',
            'invoice_type_id',
            'price_net',
            'price_gross',
            'vat_sum',
            'gross_counted',
            'items',
            'taxes',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_with_invalid_receipt_id()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        factory(OnlineSale::class, 3)->create();
        factory(Receipt::class)->create();

        $this->post('invoices?selected_company_id=' . $company->id, [
            'extra_item_id' => 3,
            'extra_item_type' => 'receipts',
        ])->assertResponseStatus(422);

        $this->verifyValidationResponse(
            [
                'extra_item_id',
                'sale_date',
                'issue_date',
                'payment_term_days',
                'contractor_id',
                'payment_method_id',
                'invoice_type_id',
                'price_net',
                'price_gross',
                'vat_sum',
                'gross_counted',
                'items',
                'taxes',
            ],
            [
                'extra_item_type',
            ]
        );
    }

    /** @test */
    public function store_it_returns_validation_error_with_invalid_online_sale_id()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        factory(OnlineSale::class)->create();
        factory(Receipt::class, 3)->create();

        $this->post('invoices?selected_company_id=' . $company->id, [
            'extra_item_id' => 3,
            'extra_item_type' => 'online_sales',
        ])->assertResponseStatus(422);

        $this->verifyValidationResponse(
            [
                'extra_item_id',
                'sale_date',
                'issue_date',
                'payment_term_days',
                'contractor_id',
                'payment_method_id',
                'invoice_type_id',
                'price_net',
                'price_gross',
                'vat_sum',
                'gross_counted',
                'items',
                'taxes',
            ],
            [
                'extra_item_type',
            ]
        );
    }

    /** @test */
    public function test_store_it_returns_validation_error_with_invalid_extra_item_type()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        factory(Receipt::class)->create();

        $this->post('invoices?selected_company_id=' . $company->id, [
            'extra_item_id' => [1],
            'extra_item_type' => 'test',
            'description' => 200,
        ])->assertResponseStatus(422);

        $this->verifyValidationResponse(
            [
                'extra_item_type',
                'description',
                'sale_date',
                'issue_date',
                'payment_term_days',
                'contractor_id',
                'payment_method_id',
                'invoice_type_id',
                'price_net',
                'price_gross',
                'vat_sum',
                'gross_counted',
                'items',
                'taxes',
            ],
            [
                'extra_item_id',
            ]
        );
    }

    /** @test */
    public function store_it_returns_validation_error_empty_items()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE,
            false
        );

        $incoming_data = [
            'items' => [[]],
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'items.0.company_service_id',
            'items.0.price_gross_sum',
            'items.0.vat_sum',
            'items.0.vat_rate_id',
            'items.0.quantity',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_empty_items_custom_names_on()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE,
            true
        );

        $incoming_data = [
            'items' => [[]],
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'items.0.company_service_id',
            'items.0.price_gross_sum',
            'items.0.vat_sum',
            'items.0.vat_rate_id',
            'items.0.quantity',
            'items.0.custom_name',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_empty_taxes()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $incoming_data = [
            'taxes' => [[]],
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'taxes.0.vat_rate_id',
            'taxes.0.price_net',
            'taxes.0.price_gross',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_gross_counted()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $incoming_data = [
            'gross_counted' => 'not_boolean',
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'gross_counted',
        ]);

        $incoming_data = [
            'gross_counted' => 2,
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'gross_counted',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_required_item_price_net_if_gross_counted_is_false()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $incoming_data = [
            'gross_counted' => false,
            'items' => [[]],
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'items.0.price_net',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_required_item_price_gross_if_gross_counted_is_true()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $incoming_data = [
            'gross_counted' => true,
            'items' => [[]],
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'items.0.price_gross',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_corrected_invoice_id()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $incoming_data = [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            'corrected_invoice_id' => factory(Invoice::class)->create()->id,

        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'corrected_invoice_id',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_custom_name()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE,
            true
        );
        $incoming_data = [
            'items' => [
                [
                    'custom_name' => [],
                ],
            ],
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'items.0.custom_name',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_corrected_correction_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $invoice = factory(Invoice::class)->create();
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id;
        $invoice->save();
        $incoming_data = [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            'corrected_invoice_id' => $invoice->id,
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'corrected_invoice_id',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_proforma_disabled()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_PROFORMA_ENABLED,
            false
        );
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA);
        $incoming_data = [
            'invoice_type_id' => $invoice_type->id,
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'invoice_type_id',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_corrected_invoice_not_exists()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $invoice = factory(Invoice::class)->create();
        $fake_invoice_id = $invoice->id;
        $invoice->delete();
        $incoming_data = [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            'corrected_invoice_id' => $invoice->id,
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(404);
    }

    /** @test */
    public function store_it_returns_validation_error_contractor_correction_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $contractor = factory(Contractor::class)->create();
        $contractor_other = factory(Contractor::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $invoice = factory(Invoice::class)->create();
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->save();
        $incoming_data = [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            'corrected_invoice_id' => $invoice->id,
            'contractor_id' => $contractor_other->id,

        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'contractor_id',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_correction_type_correction_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $contractor = factory(Contractor::class)->create();
        $contractor_other = factory(Contractor::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $invoice = factory(Invoice::class)->create();
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->save();
        $incoming_data = [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            'corrected_invoice_id' => $invoice->id,
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'correction_type',
        ]);

        $incoming_data = [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            'corrected_invoice_id' => $invoice->id,
            'correction_type' => Invoice::TYPE_CORRECTION,
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'correction_type',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_gross_counted_correction_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $invoice = factory(Invoice::class)->create();
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->gross_counted = 0;
        $invoice->save();
        $incoming_data = [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            'corrected_invoice_id' => $invoice->id,
            'gross_counted' => 1,

        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'gross_counted',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_to_low_correction_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $invoice = factory(Invoice::class)->create();
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->save();
        $incoming_data = [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            'corrected_invoice_id' => $invoice->id,
            'price_net' => -10000000,
            'price_gross' => -10000000,
            'vat_sum' => -10000000,
            'items' => [
                [
                    'position_corrected_id' => $invoice_item->id,
                    'price_net_sum' => -10000000,
                    'price_gross_sum' => -10000000,
                    'vat_sum' => -10000000,
                    'quantity' => -10000000,
                ],
            ],
            'taxes' => [
                [
                    'price_net' => -10000000,
                    'price_gross' => -10000000,
                ],
            ],

        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'price_net',
            'price_gross',
            'vat_sum',
            'items.0.price_net_sum',
            'items.0.price_gross_sum',
            'items.0.vat_sum',
            'items.0.quantity',
            'taxes.0.price_net',
            'taxes.0.price_gross',
        ]);
    }

    /** @test */
    public function store_it_return_validation_error_record_not_exists()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $contractor = factory(Contractor::class)->create();
        $fake_contractor_id = $contractor->id;
        Contractor::destroy($contractor->id);
        $payment_method = factory(PaymentMethod::class)->create();
        $fake_payment_method_id = $payment_method->id;
        PaymentMethod::destroy($payment_method->id);
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $fake_invoice_type_id = $invoice_type->id;
        InvoiceType::destroy($invoice_type->id);
        $company_service = factory(CompanyService::class)->create();
        $fake_company_service_id = $company_service->id;
        CompanyService::destroy($company_service->id);
        $vat_rate = factory(VatRate::class)->create();
        $fake_rate_id = $vat_rate->id;
        VatRate::destroy($vat_rate->id);

        $incoming_data = [
            'contractor_id' => $fake_contractor_id,
            'payment_method_id' => $fake_payment_method_id,
            'invoice_type_id' => $fake_invoice_type_id,
            'items' => [
                [
                    'company_service_id' => $fake_company_service_id,
                    'vat_rate_id' => $fake_rate_id,
                ],
            ],
            'taxes' => [
                [
                    'vat_rate_id' => $fake_rate_id,
                ],
            ],
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'contractor_id',
            'payment_method_id',
            'invoice_type_id',
            'items.0.company_service_id',
            'items.0.vat_rate_id',
            'taxes.0.vat_rate_id',
        ]);
    }

    /** @test */
    public function store_it_return_validation_error_date_inputs()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $incoming_data = [
            'sale_date' => 'no_date_string_format',
            'issue_date' => 'no_date_string_format',

        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'sale_date',
            'issue_date',

        ]);
    }

    /** @test */
    public function test_store_it_return_validation_error_numeric_inputs()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $incoming_data = [
            'payment_term_days' => 'not_numeric',
            'price_net' => 'not_numeric',
            'price_gross' => 'not_numeric',
            'vat_sum' => 'not_numeric',
            'items' => [
                [
                    'price_net' => 'not_numeric',
                    'price_net_sum' => 'not_numeric',
                    'price_gross_sum' => 'not_numeric',
                    'vat_sum' => 'not_numeric',
                    'quantity' => 'not_numeric',
                ],
            ],
            'taxes' => [
                [

                    'price_net' => 'not_numeric',
                    'price_gross' => 'not_numeric',
                ],
            ],
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'payment_term_days',
            'price_net',
            'price_gross',
            'vat_sum',
            'items.0.price_net',
            'items.0.price_net_sum',
            'items.0.price_gross_sum',
            'items.0.vat_sum',
            'items.0.quantity',
            'taxes.0.price_net',
            'taxes.0.price_gross',
        ]);
    }

    /** @test */
    public function store_it_return_validation_error_amount_to_much()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $incoming_data = [
            'payment_term_days' => 200,
            'price_net' => 10000000,
            'price_gross' => 10000000,
            'vat_sum' => 10000000,
            'items' => [
                [
                    'price_net' => 10000000,
                    'price_net_sum' => 10000000,
                    'price_gross_sum' => 10000000,
                    'vat_sum' => 10000000,
                    'quantity' => 10000000,
                ],
            ],
            'taxes' => [
                [
                    'price_net' => 10000000,
                    'price_gross' => 10000000,
                ],
            ],
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'payment_term_days',
            'price_net',
            'price_gross',
            'vat_sum',
            'items.0.price_net',
            'items.0.price_net_sum',
            'items.0.price_gross_sum',
            'items.0.vat_sum',
            'items.0.quantity',
            'taxes.0.price_net',
            'taxes.0.price_gross',
        ]);
    }

    /** @test */
    public function store_it_return_validation_error_description_too_long()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $incoming_data = [
           'description' => Factory::create()->words(1000),
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'description',
        ]);
    }

    /** @test */
    public function store_it_return_validation_error_amount_to_low()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $incoming_data = [
            'payment_term_days' => -200,
            'price_net' => 0,
            'price_gross' => 0,
            'vat_sum' => -1,
            'items' => [
                [
                    'price_net' => 0,
                    'price_net_sum' => 0,
                    'price_gross_sum' => 0,
                    'vat_sum' => -1,
                    'quantity' => 0,
                ],
            ],
            'taxes' => [
                [
                    'price_net' => 0,
                    'price_gross' => 0,
                ],
            ],
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'payment_term_days',
            'price_net',
            'price_gross',
            'vat_sum',
            'items.0.price_net',
            'items.0.price_net_sum',
            'items.0.price_gross_sum',
            'items.0.vat_sum',
            'items.0.quantity',
            'taxes.0.price_net',
            'taxes.0.price_gross',

        ]);
    }

    /** @test */
    public function store_it_return_validation_error_contractor_from_other_company()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $company_other = factory(Company::class)->create();
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company_other;
        $contractor->save();

        $incoming_data = [
            'contractor_id' => $contractor->id,
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'contractor_id',

        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_correction_invoice_item_id()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $invoice_item = factory(InvoiceItem::class)->create();
        $fake_invoice_item_id = $invoice_item->id;
        $invoice_item->delete();
        $invoice = factory(Invoice::class)->create();
        $incoming_data = [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            'corrected_invoice_id' => $invoice->id,
            'items' => [
                [
                    'position_corrected_id' => $fake_invoice_item_id,
                ],
            ],
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'items.0.position_corrected_id',
        ]);

        $invoice_item = factory(InvoiceItem::class)->create();
        $invoice_other = factory(Invoice::class)->create();
        $invoice_item->invoice_id = $invoice_other->id;
        $invoice_item->save();
        $incoming_data = [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            'corrected_invoice_id' => $invoice->id,
            'items' => [
                [
                    'position_corrected_id' => $invoice_item->id,
                ],
            ],
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'items.0.position_corrected_id',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_correction_invoice_item_id_lack_position_invoice_id()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoice = factory(Invoice::class)->create();
        $invoice_item = factory(InvoiceItem::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        $incoming_data = [
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION)->id,
            'corrected_invoice_id' => $invoice->id,
            'items' => [
                [
                ],
            ],
        ];
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'items.0.position_corrected_id',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_issue_date_for_cash_and_card_payments()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $now = Carbon::parse('2017-01-01');
        Carbon::setTestNow($now);
        $incoming_data = [
            'payment_method_id' => $payment_method::findBySlug(PaymentMethodType::CASH)->id,
            'issue_date' => $now->toDateString(),
            'payment_term_days' => 1,
            'sale_date' => $now->toDateString(),
        ];

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'payment_term_days',
        ]);

        $incoming_data = [
            'payment_method_id' => $payment_method::findBySlug(PaymentMethodType::DEBIT_CARD)->id,
            'issue_date' => $now->toDateString(),
            'payment_term_days' => 11,
            'sale_date' => $now->toDateString(),
        ];

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'payment_term_days',
        ]);

        $incoming_data = [
            'payment_method_id' => $payment_method::findBySlug(PaymentMethodType::CASH_CARD)->id,
            'issue_date' => $now->toDateString(),
            'payment_term_days' => 11,
            'sale_date' => $now->toDateString(),
        ];

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'payment_term_days',
        ]);
    }

    /** @test */
    public function store_loose_couplet_between_payment_in_advance_and_payment_term_days_for_proforma()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->setAppSettings($company, ModuleType::INVOICES_PROFORMA_ENABLED, true);
        $payment_method = factory(PaymentMethod::class)->create();
        $now = Carbon::parse('2017-01-01');
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA);
        Carbon::setTestNow($now);
        $incoming_data = [
            'payment_method_id' => $payment_method::findBySlug(PaymentMethodType::CASH)->id,
            'issue_date' => $now->toDateString(),
            'payment_term_days' => 11,
            'sale_date' => $now->toDateString(),
            'invoice_type_id' => $invoice_type->id,
        ];

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse(['items'], [
            'payment_term_days',
        ]);
    }

    /** @test */
    public function store_address_contractor_from_other_company_validation_error()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        $other_company = factory(Company::class)->create();
        $other_contractor = factory(Contractor::class)->create([
            'company_id' => $other_company->id,
        ]);
        $delivery_address->contractor_id = $other_contractor->id;
        $delivery_address->save();
        unset($incoming_data['default_delivery']);
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'delivery_address_id',
            'default_delivery',
        ]);
    }

    /** @test */
    public function store_delivery_address_contractor_not_indicate_as_delivery_validation_error()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        $delivery_address->type = 'not_delivery_address';
        $delivery_address->save();
        $incoming_data['default_delivery'] = 'no_valid_boolean';
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
        $this->verifyValidationResponse([
            'delivery_address_id',
            'default_delivery',
        ]);
    }

    /** @test */
    public function store_it_returns_validation_error_correction_company_service_id()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type_model = factory(InvoiceType::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $invoice = factory(Invoice::class)->create();
        $invoice->company_id = $company->id;
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->payment_method_id = $payment_method->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->invoice_registry_id = $this->registry->id;
        $invoice->save();
        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[2]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(421);
        $this->assertSame(
            ErrorCode::INVOICE_PROTECT_CORRECTION_OF_COMPANY_SERVICE,
            $this->decodeResponseJson()['code']
        );
    }

    /** @test */
    public function store_it_return_error_duplicate_invoice_for_single_receipt()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $receipt = factory(Receipt::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice->receipts()->attach($receipt->id);
        $this->assertSame(1, InvoiceReceipt::count());
        $incoming_data = array_merge($incoming_data, [
            'extra_item_id' => [$receipt->id],
            'extra_item_type' => 'receipts',
        ]);
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(423);
        $this->verifyErrorResponse(
            423,
            ErrorCode::INVOICES_DUPLICATE_INVOICE_FOR_SINGLE_SALE_DOCUMENT
        );
    }

    /** @test */
    public function store_it_return_error_has_correction()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $invoice = factory(Invoice::class)->create();
        $invoice->company_id = $company->id;
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->payment_method_id = $payment_method->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->invoice_registry_id = $this->registry->id;
        $invoice->save();
        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[1]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoice->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoice->id;
        $invoice_contractor->save();
        $payment_method_model = $this->app->make(PaymentMethod::class);
        $payment_method = $payment_method_model->findBySlug(PaymentMethodType::BANK_TRANSFER);
        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );
        $bank_account = $company->bankAccounts()->save(factory(BankAccount::class)->make());
        array_set($incoming_data, 'bank_account_id', $bank_account->id);
        $receipt = factory(Receipt::class)->create([
            'company_id' => $company->id,
        ]);

        InvoiceReceipt::whereRaw('1=1')->delete();
        InvoiceOnlineSale::whereRaw('1=1')->delete();

        $invoice->receipts()->attach($receipt->id);
        $this->assertSame(1, InvoiceReceipt::count());

        //deleted "correction
        $invoice_next = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice->parentInvoices()->save($invoice_next);

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(424);
    }

    /** @test */
    public function store_company_has_disabled_delivery_address_on_invoices()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, false);
        $incoming_data['delivery_address_id'] = $delivery_address->id;
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertSame(0, InvoiceDeliveryAddress::count());
        $invoice = Invoice::latest()->first();
        $this->assertNull($invoice->delivery_adddress_id);
        $this->assertTrue((bool) $invoice->default_delivery);
    }

    /** @test */
    public function store_company_has_disabled_delivery_address_and_not_incoming()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, false);
        unset($incoming_data['delivery_address_id'], $incoming_data['default_delivery']);

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertSame(0, InvoiceDeliveryAddress::count());
        $invoice = Invoice::latest()->first();
        $this->assertNull($invoice->delivery_adddress_id);
        $this->assertTrue((bool) $invoice->default_delivery);
    }

    /** @test */
    public function store_company_has_enabled_delivery_address_validation_error()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        unset($incoming_data['delivery_address_id']);
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'delivery_address_id',
        ]);
    }

    /** @test */
    public function store_bank_account_validation_error_for_bank_transfer_payment_method()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->registry->company_id = $company->id;
        $this->registry->save();

        $incoming_data['bank_account_id'] = factory(BankAccount::class)->create()->id;

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'bank_account_id',
        ]);
    }

    /** @test */
    public function store_company_has_enabled_delivery_address_validation_error_address_not_belongs_to_contractor()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        $fake_contractor = factory(Contractor::class)->create();
        $incoming_data['delivery_address_id'] = $fake_contractor->id;
        $fake_contractor->delete();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'delivery_address_id',
        ]);
    }

    /** @test */
    public function store_add_delivery_address_to_database()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $initial_delivery_addresses = InvoiceDeliveryAddress::count();
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertSame($initial_delivery_addresses + 1, InvoiceDeliveryAddress::count());
        $invoice = Invoice::latest()->first();
        $this->assertFalse((bool) $invoice->default_delivery);
        $invoice_delivery_address = InvoiceDeliveryAddress::latest()->first();
        $this->assertSame($delivery_address->id, $invoice->delivery_address_id);
        $this->assertSame($invoice->id, $invoice_delivery_address->invoice_id);
        $this->assertSame($delivery_address->street, $invoice_delivery_address->street);
        $this->assertEquals($delivery_address->number, $invoice_delivery_address->number);
        $this->assertEquals($delivery_address->zip_code, $invoice_delivery_address->zip_code);
        $this->assertSame($delivery_address->city, $invoice_delivery_address->city);
        $this->assertSame($delivery_address->country, $invoice_delivery_address->country);
        $this->assertSame($delivery_address->contractor_id, $invoice_delivery_address->receiver_id);
        $this->assertSame($delivery_address->contractor->name, $invoice_delivery_address->receiver_name);
    }

    /** @test */
    public function store_bank_account_for_bank_transfer_payment_method()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $bank_account = $company->bankAccounts()->save(factory(BankAccount::class)->make());
        array_set($incoming_data, 'payment_method_id', PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER)->id);
        array_set($incoming_data, 'bank_account_id', $bank_account->id);

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();
        $this->assertSame($bank_account->id, $invoice->bank_account_id);
        $this->assertSame($bank_account->bank_name, $invoice->invoiceCompany->bank_name);
        $this->assertSame($bank_account->number, $invoice->invoiceCompany->bank_account_number);
    }

    /** @test */
    public function store_it_checks_if_registry_is_used_value_changed()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->assertEquals(0, $this->registry->is_used);
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertEquals(1, $this->registry->fresh()->is_used);
    }

    /** @test */
    public function store_it_checks_if_new_register_will_set_new_invoice_numeration()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->registry->company_id = $company->id;
        $this->registry->prefix = 'TEST';
        $this->registry->save();
        $this->assertEquals(0, $this->registry->is_used);
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);
        $this->assertEquals('TEST/1/01/2017', $this->response->getData()->data->number);

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);
        $this->assertEquals('TEST/2/01/2017', $this->response->getData()->data->number);

        // adding next invoice to new registry
        $new_registry = factory(InvoiceRegistry::class)->create([
            'prefix' => 'ABC',
            'company_id' => $company->id,
        ]);
        $incoming_data['invoice_registry_id'] = $new_registry->id;

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);
        $this->assertEquals('ABC/1/01/2017', $this->response->getData()->data->number);

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);
        $this->assertEquals('ABC/2/01/2017', $this->response->getData()->data->number);
    }

    /** @test */
    public function store_copy_invoice_delivery_address_for_correction()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();

        $delivery_address = factory(ContractorAddress::class)->create([
            'contractor_id' => $contractor->id,
        ]);
        $invoice = factory(Invoice::class)->create();
        $invoice->company_id = $company->id;
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->payment_method_id = $payment_method->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->delivery_address_id = $delivery_address->id;
        $invoice->invoice_registry_id = $this->registry->id;
        $invoice->save();
        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[1]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoice->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoice->id;
        $invoice_contractor->save();
        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );
        $initial_delivery_addresses = InvoiceDeliveryAddress::count();
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        unset($incoming_data['delivery_address_id'], $incoming_data['default_delivery']);

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);
        $this->assertSame($initial_delivery_addresses + 1, InvoiceDeliveryAddress::count());
        $invoice = Invoice::where('invoice_type_id', $invoice_type->id)->first();
        $this->assertTrue((bool) $invoice->default_delivery);
        $invoice_delivery_address = InvoiceDeliveryAddress::where('invoice_id', $invoice->id)->first();
        $this->assertSame($delivery_address->id, $invoice->delivery_address_id);
        $this->assertSame($invoice->id, $invoice_delivery_address->invoice_id);
        $this->assertSame($delivery_address->street, $invoice_delivery_address->street);
        $this->assertEquals($delivery_address->number, $invoice_delivery_address->number);
        $this->assertEquals($delivery_address->zip_code, $invoice_delivery_address->zip_code);
        $this->assertSame($delivery_address->city, $invoice_delivery_address->city);
        $this->assertSame($delivery_address->country, $invoice_delivery_address->country);
        $this->assertSame($delivery_address->contractor_id, $invoice_delivery_address->receiver_id);
        $this->assertSame($delivery_address->contractor->name, $invoice_delivery_address->receiver_name);
    }

    /** @test */
    public function store_it_gets_error_for_correction_invoice_when_decimals_send_to_non_decimal_services()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();

        $delivery_address = factory(ContractorAddress::class)->create([
            'contractor_id' => $contractor->id,
        ]);
        $invoice = factory(Invoice::class)->create();
        $invoice->company_id = $company->id;
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->payment_method_id = $payment_method->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->delivery_address_id = $delivery_address->id;
        $invoice->invoice_registry_id = $this->registry->id;
        $invoice->save();
        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[1]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoice->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoice->id;
        $invoice_contractor->save();
        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );

        $incoming_data['items'][0]['service_unit_id'] = ServiceUnit::findBySlug(ServiceUnit::KILOGRAM)->id;
        $incoming_data['items'][0]['quantity'] = 512.23;

        $incoming_data['items'][1]['service_unit_id'] = ServiceUnit::findBySlug(ServiceUnit::RUNNING_METRE)->id;
        $incoming_data['items'][1]['quantity'] = 512.23;

        $incoming_data['items'][2]['service_unit_id'] = ServiceUnit::findBySlug(ServiceUnit::HOUR)->id;
        $incoming_data['items'][2]['quantity'] = 512.23;

        $incoming_data['items'][3]['service_unit_id'] = ServiceUnit::findBySlug(ServiceUnit::SERVICE)->id;
        $incoming_data['items'][3]['quantity'] = 512.23;

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data);

        $this->verifyValidationResponse(['items.2.quantity','items.3.quantity'], ['items.0.quantity','items.1.quantity']);
    }

    /** @test */
    public function test_store_it_passing_issue_invoice_for_new_receipt()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $receipt = factory(Receipt::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice->receipts()->attach($receipt->id);
        $this->assertSame(1, InvoiceReceipt::count());

        $receipt_other = factory(Receipt::class)->create([
            'company_id' => $company->id,
        ]);
        $incoming_data = array_merge($incoming_data, [
            'extra_item_id' => [$receipt_other->id],
            'extra_item_type' => 'receipts',
        ]);
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);
    }

    /** @test */
    public function test_store_it_creates_invoice_for_few_receipts()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $receipt = factory(Receipt::class)->create([
            'id' => 1,
            'company_id' => $company->id,
        ]);
        $receipt_2 = factory(Receipt::class)->create([
            'id' => 2,
            'company_id' => $company->id,
        ]);
        $receipt_3 = factory(Receipt::class)->create([
            'id' => 3,
            'company_id' => $company->id,
        ]);
        $incoming_data = array_merge($incoming_data, [
            'extra_item_id' => [
                $receipt->id,
                $receipt_2->id,
                $receipt_3->id,
            ],
            'extra_item_type' => 'receipts',
        ]);

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);
        $response_data = $this->response->getData()->data;

        // Check base_document_id pass to invoice_item elements
        $this->assertEquals(
            collect($incoming_data['items'])->pluck('base_document_id')->sort(),
            InvoiceItem::all()->pluck('base_document_id')->sort()
        );
        // Check paid_at = sale_date of first receipt
        $this->assertEquals(
            $incoming_data['issue_date'] . ' 00:00:00',
            $response_data->paid_at
        );
        // Check if payment method is set to other
        $this->assertEquals(PaymentMethodType::OTHER, $response_data->payment_method->data->slug);
    }

    /** @test */
    public function store_it_creates_invoice_for_few_online_sale()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $online_sale = factory(OnlineSale::class)->create([
            'id' => 1,
            'company_id' => $company->id,
        ]);
        $online_sale_2 = factory(OnlineSale::class)->create([
            'id' => 2,
            'company_id' => $company->id,
        ]);
        $online_sale_3 = factory(OnlineSale::class)->create([
            'id' => 3,
            'company_id' => $company->id,
        ]);
        $incoming_data = array_merge($incoming_data, [
            'extra_item_id' => [
                $online_sale->id,
                $online_sale_2->id,
                $online_sale_3->id,
            ],
            'extra_item_type' => 'online_sales',
        ]);

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);
        $response_data = $this->response->getData()->data;

        // Check base_document_id pass to invoice_item elements
        $this->assertEquals(
            collect($incoming_data['items'])->pluck('base_document_id')->sort(),
            InvoiceItem::all()->pluck('base_document_id')->sort()
        );
        // Check paid_at = sale_date of first receipt
        $this->assertEquals(
            $incoming_data['issue_date'] . ' 00:00:00',
            $response_data->paid_at
        );
        // Check if payment method is set to other
        $this->assertEquals(PaymentMethodType::OTHER, $response_data->payment_method->data->slug);
    }

    /** @test */
    public function store_it_return_issuing_invoice_correction_for_single_sale_document()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $invoice = factory(Invoice::class)->create();
        $invoice->company_id = $company->id;
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->payment_method_id = $payment_method->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->invoice_registry_id = $this->registry->id;
        $invoice->save();
        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[1]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoice->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoice->id;
        $invoice_contractor->save();
        $payment_method_model = $this->app->make(PaymentMethod::class);
        $payment_method = $payment_method_model->findBySlug(PaymentMethodType::BANK_TRANSFER);
        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );
        $bank_account = $company->bankAccounts()->save(factory(BankAccount::class)->make());
        array_set($incoming_data, 'bank_account_id', $bank_account->id);
        $receipt = factory(Receipt::class)->create([
            'company_id' => $company->id,
        ]);

        InvoiceReceipt::whereRaw('1=1')->delete();
        InvoiceOnlineSale::whereRaw('1=1')->delete();

        $invoice->receipts()->attach($receipt->id);
        $this->assertSame(1, InvoiceReceipt::count());

        $initial_invoice_amount = Invoice::count();
        $invoice_registry = InvoiceRegistry::where('company_id', $company->id)->first();

        $init_receipt_amount = Receipt::count();

        //deleted "correction
        $invoice_next = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice->parentInvoices()->save($invoice_next);
        $invoice_next->delete();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();
        $this->assertSame($init_receipt_amount, Receipt::count());
        $this->assertSame(2, InvoiceReceipt::count());
        $this->assertSame(0, InvoiceOnlineSale::count());
        $this->assertSame($receipt->id, $invoice->receipts()->first()->id);
        $invoice_expect_data = $this->invoiceExpectDataCorrectionInvoice($invoice_items);
        $this->assertSame($initial_invoice_amount + 1, Invoice::count());
        $this->assertSame('KOR/' . $this->registry->prefix . '/1/01/2017', $invoice->number);
        $this->assertSame(1, $invoice->order_number);
        $this->assertSame($invoice_registry->id, $invoice->invoice_registry_id);
        $this->assertSame($this->user->id, $invoice->drawer_id);
        $this->assertSame($company->id, $invoice->company_id);
        $this->assertSame($incoming_data['corrected_invoice_id'], $invoice->corrected_invoice_id);
        $this->assertSame($incoming_data['correction_type'], $invoice->correction_type);
        $this->assertEquals('2017-01-15', $invoice->sale_date);
        $this->assertEquals($incoming_data['issue_date'], $invoice->issue_date);
        $this->assertSame($incoming_data['payment_term_days'], $invoice->payment_term_days);
        $this->assertSame($incoming_data['contractor_id'], $invoice->contractor_id);
        $this->assertSame($incoming_data['invoice_type_id'], $invoice->invoice_type_id);
        $this->assertSame($invoice_expect_data['price_net'], $invoice->price_net);
        $this->assertSame($invoice_expect_data['price_gross'], $invoice->price_gross);
        $this->assertSame($invoice_expect_data['vat_sum'], $invoice->vat_sum);
        $this->assertNull($invoice->payment_left);
        $this->assertSame($payment_method->id, $invoice->payment_method_id);
        $this->assertNull($invoice->last_printed_at);
        $this->assertNull($invoice->last_send_at);
        $this->assertNotNull($invoice->created_at);
        $this->assertNull($invoice->update_at);
        $this->assertNull($invoice->paid_at);
        $this->assertSame((int) $incoming_data['gross_counted'], $invoice->gross_counted);
        $this->assertNull($invoice->description);

        $invoice_items_expect = $this->invoiceExpectDataCorrectionInvoice($invoice_items)['items'];
        $invoice_items = InvoiceItem::latest('id')->take(4)->get();

        $i = 4;
        foreach ($invoice_items as $invoice_item) {
            $this->assertSame($invoice->id, $invoice_item->invoice_id);
            $this->assertSame($company_services[--$i]->id, $invoice_item->company_service_id);
            $this->assertSame($company_services[$i]->name, $invoice_item->name);
            $this->assertSame($invoice_items_expect[$i]['price_net'], $invoice_item->price_net);
            $this->assertSame(
                $invoice_items_expect[$i]['price_net_sum'],
                $invoice_item->price_net_sum
            );
            $this->assertNull($invoice_item->price_gross);
            $this->assertSame(
                $invoice_items_expect[$i]['price_gross_sum'],
                $invoice_item->price_gross_sum
            );
            $this->assertEquals($vat_rate->rate, $invoice_item->vat_rate);
            $this->assertSame($vat_rate->id, $invoice_item->vat_rate_id);
            $this->assertSame($invoice_items_expect[$i]['vat_sum'], $invoice_item->vat_sum);
            $this->assertSame($invoice_items_expect[$i]['quantity'], $invoice_item->quantity);
            $this->assertTrue((bool) $invoice_item->is_correction);
            $this->assertSame(
                $invoice_items_expect[$i]['position_corrected_id'],
                $invoice_item->position_corrected_id
            );
            $this->assertSame($this->user->id, $invoice_item->creator_id);
            $this->assertFalse((bool) $invoice_item->editor_id);
            $this->assertNotNull($invoice_item->created_at);
            $this->assertNotNull($invoice_item->updated_at);
        }
    }

    /** @test */
    public function store_it_return_issuing_invoice_correction_for_many_sale_documents()
    {
        list($company, $invoice, $invoice_items, $incoming_data) = $this->setFinancialEnvironmentForCorrection();

        $receipts = factory(Receipt::class, 3)->create([
            'company_id' => $company->id,
        ]);

        InvoiceReceipt::whereRaw('1=1')->delete();
        InvoiceOnlineSale::whereRaw('1=1')->delete();

        $invoice->receipts()->attach($receipts->pluck('id')->toArray());
        $this->assertSame(3, InvoiceReceipt::count());

        $initial_invoice_amount = Invoice::count();
        $init_receipt_amount = Receipt::count();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);
        $response_data = $this->response->getData()->data;

        $invoice = Invoice::latest('id')->first();
        $this->assertSame($init_receipt_amount, Receipt::count());
        $this->assertSame(6, InvoiceReceipt::count());
        $this->assertSame(0, InvoiceOnlineSale::count());
        $this->assertSame($receipts[0]->id, $invoice->receipts()->first()->id);
        $this->assertSame($receipts[1]->id, $invoice->receipts()->skip(1)->first()->id);
        $this->assertSame($receipts[2]->id, $invoice->receipts()->skip(2)->first()->id);
    }

    /** @test */
    public function store_if_empty_invoice_delivery_address_for_correction()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $invoice = factory(Invoice::class)->create();
        $invoice->company_id = $company->id;
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->payment_method_id = $payment_method->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->invoice_registry_id = $this->registry->id;
        $invoice->save();
        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[1]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoice->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoice->id;
        $invoice_contractor->save();

        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );

        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        unset($incoming_data['delivery_address_id'], $incoming_data['default_delivery']);

        $initial_invoice_amount = Invoice::count();
        $invoice_registry = InvoiceRegistry::where('company_id', $company->id)->first();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertSame(0, InvoiceDeliveryAddress::count());
        $invoice = Invoice::where('invoice_type_id', $invoice_type->id)->first();
        $this->assertTrue((bool) $invoice->default_delivery);
        $this->assertNull($invoice->delivery_address_id);
    }

    /** @test */
    public function store_it_return_issuing_invoice_correction_for_online_sale_ignoring_extra_item_id()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $invoice = factory(Invoice::class)->create();
        $invoice->company_id = $company->id;
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->payment_method_id = $payment_method->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->invoice_registry_id = $this->registry->id;
        $invoice->save();
        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[1]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoice->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoice->id;
        $invoice_contractor->save();
        $payment_method_model = $this->app->make(PaymentMethod::class);
        $payment_method = $payment_method_model->findBySlug(PaymentMethodType::BANK_TRANSFER);
        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );
        $bank_account = $company->bankAccounts()->save(factory(BankAccount::class)->make());
        array_set($incoming_data, 'bank_account_id', $bank_account->id);

        $receipt = factory(Receipt::class)->create([
            'company_id' => $company->id,
        ]);
        $incoming_data = array_merge($incoming_data, [
            'extra_item_id' => [$receipt->id],
            'extra_item_type' => 'receipts',
        ]);
        $online_sale = factory(OnlineSale::class)->create([
            'company_id' => $company->id,
        ]);

        InvoiceReceipt::whereRaw('1=1')->delete();
        InvoiceOnlineSale::whereRaw('1=1')->delete();

        $invoice->onlineSales()->attach($online_sale->id);
        $this->assertSame(1, InvoiceOnlineSale::count());

        $init_receipts_amount = Receipt::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();
        $this->assertSame($init_receipts_amount, Receipt::count());
        $this->assertSame(1, OnlineSale::count());
        $this->assertSame(0, InvoiceReceipt::count());
        $this->assertSame(2, InvoiceOnlineSale::count());
        $this->assertSame($online_sale->id, $invoice->onlineSales()->first()->id);
    }

    /** @test */
    public function store_it_return_error_duplicate_invoice_for_single_online_sale()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $online_sale = factory(OnlineSale::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice->onlineSales()->attach($online_sale->id);
        $this->assertSame(1, InvoiceOnlineSale::count());
        $incoming_data = array_merge($incoming_data, [
            'extra_item_id' => [$online_sale->id],
            'extra_item_type' => 'online_sales',
        ]);
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(423);
        $this->verifyErrorResponse(
            423,
            ErrorCode::INVOICES_DUPLICATE_INVOICE_FOR_SINGLE_SALE_DOCUMENT
        );
    }

    /** @test */
    public function store_it_passing_validation()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);
    }

    /** @test */
    public function store_it_passing_validation_correction_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type_model = factory(InvoiceType::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $invoice = factory(Invoice::class)->create();
        $invoice->company_id = $company->id;
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->payment_method_id = $payment_method->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->invoice_registry_id = $this->registry->id;
        $invoice->save();
        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[1]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoice->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoice->id;
        $invoice_contractor->save();
        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);
    }

    /** @test */
    public function store_it_add_invoice_with_correction_data()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type_model = factory(InvoiceType::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::VAT);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $invoice = factory(Invoice::class)->create();
        $invoice->company_id = $company->id;
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->payment_method_id = $payment_method->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->save();
        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[1]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoice->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoice->id;
        $invoice_contractor->save();
        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);
    }

    /** @test */
    public function store_it_returns_error_register_not_found()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        InvoiceRegistry::whereRaw('1=1')->delete();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'invoice_registry_id',
        ]);
    }

    /** @test */
    public function store_it_correct_first_order_number()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $invoice_registry = InvoiceRegistry::inCompany($company)->first();
        $invoices = factory(Invoice::class, 2)->create();
        $invoices[0]->invoice_registry_id = $invoice_registry->id;
        $invoices[0]->issue_date = Carbon::parse('2017-02-01');
        $invoices[0]->order_number_date = Carbon::parse('2017-02-01');
        $invoices[0]->order_number = 1;
        $invoices[0]->company_id = $company->id;
        $invoices[0]->save();
        $invoices[1]->invoice_registry_id = $invoice_registry->id;
        $invoices[1]->issue_date = Carbon::parse('2016-12-01');
        $invoices[1]->order_number = 1;
        $invoices[1]->company_id = $company->id;
        $invoices[1]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoices[0]->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoices[0]->id;
        $invoice_contractor->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoices[1]->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoices[1]->id;
        $invoice_contractor->save();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();

        $this->assertSame(1, $invoice->order_number);
    }

    /** @test */
    public function store_it_correct_next_order_number()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $invoices = factory(Invoice::class, 2)->create();
        $invoices[0]->invoice_registry_id = $this->registry->id;
        $invoices[0]->invoice_type_id = $invoice_type->id;
        $invoices[0]->issue_date = Carbon::parse('2017-01-01');
        $invoices[0]->order_number_date = Carbon::parse('2017-01-01');
        $invoices[0]->order_number = 1;
        $invoices[0]->company_id = $company->id;
        $invoices[0]->save();
        $invoices[1]->invoice_registry_id = $this->registry->id;
        $invoices[1]->invoice_type_id = $invoice_type->id;
        $invoices[1]->issue_date = Carbon::parse('2017-01-01');
        $invoices[1]->order_number_date = Carbon::parse('2017-01-01');
        $invoices[1]->order_number = 2;
        $invoices[1]->company_id = $company->id;
        $invoices[1]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoices[0]->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoices[0]->id;
        $invoice_contractor->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoices[1]->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoices[1]->id;
        $invoice_contractor->save();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();

        $this->assertSame(3, $invoice->order_number);
    }

    /** @test */
    public function store_it_correct_next_order_number_with_deleted_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $invoices = factory(Invoice::class, 3)->create();
        $invoices[0]->invoice_registry_id = $this->registry->id;
        $invoices[0]->invoice_type_id = $invoice_type->id;
        $invoices[0]->issue_date = Carbon::parse('2017-01-01');
        $invoices[0]->order_number_date = Carbon::parse('2017-01-01');
        $invoices[0]->order_number = 1;
        $invoices[0]->company_id = $company->id;
        $invoices[0]->save();
        $invoices[1]->invoice_registry_id = $this->registry->id;
        $invoices[1]->invoice_type_id = $invoice_type->id;
        $invoices[1]->issue_date = Carbon::parse('2017-01-01');
        $invoices[1]->order_number_date = Carbon::parse('2017-01-01');
        $invoices[1]->order_number = 2;
        $invoices[1]->company_id = $company->id;
        $invoices[1]->save();
        $invoices[2]->invoice_registry_id = $this->registry->id;
        $invoices[2]->invoice_type_id = $invoice_type->id;
        $invoices[2]->issue_date = Carbon::parse('2017-01-01');
        $invoices[2]->order_number_date = Carbon::parse('2017-01-01');
        $invoices[2]->order_number = 3;
        $invoices[2]->company_id = $company->id;
        $invoices[2]->deleted_at = '2017-05-11 13:00:00';
        $invoices[2]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoices[0]->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoices[0]->id;
        $invoice_contractor->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoices[1]->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoices[1]->id;
        $invoice_contractor->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoices[2]->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoices[2]->id;
        $invoice_contractor->save();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();

        $this->assertSame(4, $invoice->order_number);
    }

    /** @test */
    public function store_it_correct_order_number_correction_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type_model = factory(InvoiceType::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $invoice = factory(Invoice::class)->create();
        $invoice->company_id = $company->id;
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->payment_method_id = $payment_method->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->invoice_registry_id = $this->registry->id;
        $invoice->save();
        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[1]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoice->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoice->id;
        $invoice_contractor->save();
        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );

        $invoices = factory(Invoice::class, 2)->create();
        $invoices[0]->invoice_registry_id = $this->registry->id;
        $invoices[0]->invoice_type_id = $invoice_type->id;
        $invoices[0]->issue_date = Carbon::parse('2017-01-01');
        $invoices[0]->order_number_date = Carbon::parse('2017-01-01');
        $invoices[0]->order_number = 1;
        $invoices[0]->company_id = $company->id;
        $invoices[0]->save();
        $invoices[1]->invoice_registry_id = $this->registry->id;
        $invoices[1]->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT);
        $invoices[1]->issue_date = Carbon::parse('2017-01-01');
        $invoices[1]->order_number = 2;
        $invoices[1]->company_id = $company->id;
        $invoices[1]->save();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();

        $this->assertSame(2, $invoice->order_number);
    }

    /** @test */
    public function store_it_correct_order_number_if_invoice_exists_in_database()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $invoice_type_other = factory(InvoiceType::class)->create();
        $company_other = factory(Company::class)->create();
        $invoices = factory(Invoice::class, 2)->create();
        $invoices[0]->invoice_registry_id = $this->registry->id;
        $invoices[0]->invoice_type_id = $invoice_type_other->id;
        $invoices[0]->issue_date = Carbon::parse('2017-01-01');
        $invoices[0]->order_number = 2;
        $invoices[0]->company_id = $company_other->id;
        $invoices[0]->save();
        $invoices[1]->invoice_registry_id = $this->registry->id;
        $invoices[1]->invoice_type_id = $invoice_type->id;
        $invoices[1]->issue_date = Carbon::parse('2017-01-01');
        $invoices[1]->order_number_date = Carbon::parse('2017-01-01');
        $invoices[1]->order_number = 1;
        $invoices[1]->company_id = $company->id;
        $invoices[1]->save();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();

        $this->assertSame(2, $invoice->order_number);
    }

    /** @test */
    public function store_it_number_monthly_with_prefix()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();

        $invoice_format = factory(InvoiceFormat::class)->create();
        $invoice_registry = factory(InvoiceRegistry::class)->create();
        $format = '{%nr}/{%m}/{%Y}';
        $invoice_registry->invoice_format_id = $invoice_format->findByFormatStrict($format)->id;
        $invoice_registry->company_id = $company->id;
        $invoice_registry->prefix = 'prefix';
        $invoice_registry->save();
        $this->registry = $invoice_registry;

        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();

        $this->assertSame('prefix/1/01/2017', $invoice->number);
    }

    /** @test */
    public function store_it_number_monthly_without_prefix()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $invoice_format = factory(InvoiceFormat::class)->create();
        $invoice_registry = factory(InvoiceRegistry::class)->create();
        $format = '{%nr}/{%m}/{%Y}';
        $invoice_registry->invoice_format_id = $invoice_format->findByFormatStrict($format)->id;
        $invoice_registry->company_id = $company->id;
        $invoice_registry->prefix = '';
        $invoice_registry->save();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();

        $this->assertSame($this->registry->prefix . '/1/01/2017', $invoice->number);
    }

    /** @test */
    public function store_it_number_years_with_prefix()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();

        $invoice_format = factory(InvoiceFormat::class)->create();
        $invoice_registry = factory(InvoiceRegistry::class)->create();
        $format = '{%nr}/{%Y}';
        $invoice_registry->invoice_format_id = $invoice_format->findByFormatStrict($format)->id;
        $invoice_registry->company_id = $company->id;
        $invoice_registry->prefix = 'prefix';
        $invoice_registry->save();
        $this->registry = $invoice_registry;

        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();

        $this->assertSame('prefix/1/2017', $invoice->number);
    }

    /** @test */
    public function store_it_number_years_without_prefix()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();

        $invoice_format = factory(InvoiceFormat::class)->create();
        $invoice_registry = factory(InvoiceRegistry::class)->create();
        $format = '{%nr}/{%Y}';
        $invoice_registry->invoice_format_id = $invoice_format->findByFormatStrict($format)->id;
        $invoice_registry->company_id = $company->id;
        $invoice_registry->prefix = '';
        $invoice_registry->save();
        $this->registry = $invoice_registry;

        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();

        $this->assertSame('1/2017', $invoice->number);
    }

    /** @test */
    public function store_it_number_corrected_invoice_with_prefix()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type_model = factory(InvoiceType::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();

        $invoice_format = factory(InvoiceFormat::class)->create();
        $invoice_registry = factory(InvoiceRegistry::class)->create();
        $format = '{%nr}/{%Y}';
        $invoice_registry->invoice_format_id = $invoice_format->findByFormatStrict($format)->id;
        $invoice_registry->company_id = $company->id;
        $invoice_registry->prefix = 'prefix';
        $invoice_registry->save();
        $this->registry = $invoice_registry;

        $invoice = factory(Invoice::class)->create();
        $invoice->company_id = $company->id;
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->payment_method_id = $payment_method->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->invoice_registry_id = $this->registry->id;
        $invoice->save();

        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[1]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoice->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoice->id;
        $invoice_contractor->save();
        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();
        $this->assertSame('KOR/prefix/1/2017', $invoice->number);
    }

    /** @test */
    public function store_passed_validation_with_equal_issue_and_sale_dates_and_cash_pay()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();

        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::CASH);
        $incoming_data['payment_method_id'] = $payment_method->id;
        array_set($incoming_data, 'sale_date', Carbon::parse('2017-01-15')->toDateString());
        array_set($incoming_data, 'issue_days', Carbon::parse('2017-01-15')->toDateString());
        array_set($incoming_data, 'payment_term_days', 0);

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);
    }

    /** @test */
    public function store_invoice_was_added_to_database()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->registry = $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $initial_invoice_amount = Invoice::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();
        $invoice_expect_data = $this->invoiceExpectData();

        $this->assertSame($initial_invoice_amount + 1, Invoice::count());
        $this->assertSame($this->registry->prefix . '/1/01/2017', $invoice->number);
        $this->assertSame(1, $invoice->order_number);
        $this->assertSame($this->registry->id, $invoice->invoice_registry_id);
        $this->assertSame($this->user->id, $invoice->drawer_id);
        $this->assertSame($company->id, $invoice->company_id);
        $this->assertNull($invoice->corrected_invoice_id);
        $this->assertNull($invoice->correction_type);
        $this->assertEquals($incoming_data['sale_date'], $invoice->sale_date);
        $this->assertEquals($incoming_data['issue_date'], $invoice->issue_date);
        $this->assertSame($incoming_data['payment_term_days'], $invoice->payment_term_days);
        $this->assertSame($incoming_data['contractor_id'], $invoice->contractor_id);
        $this->assertSame($incoming_data['invoice_type_id'], $invoice->invoice_type_id);
        $this->assertSame($invoice_expect_data['price_net'], $invoice->price_net);
        $this->assertSame($invoice_expect_data['price_gross'], $invoice->price_gross);
        $this->assertSame($invoice_expect_data['vat_sum'], $invoice->vat_sum);
        $this->assertSame($invoice_expect_data['price_gross'], $invoice->payment_left);
        $this->assertSame($incoming_data['payment_method_id'], $invoice->payment_method_id);
        $this->assertNull($invoice->bank_account_id);
        $this->assertNull($invoice->last_printed_at);
        $this->assertNull($invoice->last_send_at);
        $this->assertNotNull($invoice->created_at);
        $this->assertNull($invoice->update_at);
        $this->assertNull($invoice->paid_at);
        $this->assertSame((int) $incoming_data['gross_counted'], $invoice->gross_counted);
        $this->assertSame($incoming_data['description'], $invoice->description);
        $this->assertNull($invoice->invoice_margin_procedure_id);

        $this->assertEquals($contractor->id, $this->response->getData()->data->invoice_contractor
            ->data->contractor_id);
    }

    /** @test */
    public function store_proforma_was_added_to_database()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->registry = $this->createInvoiceRegistryForCompany($company);
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_PROFORMA_ENABLED,
            true
        );
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $initial_invoice_amount = Invoice::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();
        $invoice_expect_data = $this->invoiceExpectData();

        $this->assertSame($initial_invoice_amount + 1, Invoice::count());
        $this->assertSame('PRO/' . $this->registry->prefix . '/1/01/2017', $invoice->number);
        $this->assertSame(1, $invoice->order_number);
        $this->assertSame($this->registry->id, $invoice->invoice_registry_id);
        $this->assertSame($this->user->id, $invoice->drawer_id);
        $this->assertSame($company->id, $invoice->company_id);
        $this->assertNull($invoice->corrected_invoice_id);
        $this->assertNull($invoice->correction_type);
        $this->assertEquals($incoming_data['sale_date'], $invoice->sale_date);
        $this->assertEquals($incoming_data['issue_date'], $invoice->issue_date);
        $this->assertSame($incoming_data['payment_term_days'], $invoice->payment_term_days);
        $this->assertSame($incoming_data['contractor_id'], $invoice->contractor_id);
        $this->assertSame($invoice_type->id, $invoice->invoice_type_id);
        $this->assertSame($invoice_expect_data['price_net'], $invoice->price_net);
        $this->assertSame($invoice_expect_data['price_gross'], $invoice->price_gross);
        $this->assertSame($invoice_expect_data['vat_sum'], $invoice->vat_sum);
        $this->assertSame($invoice_expect_data['price_gross'], $invoice->payment_left);
        $this->assertSame($incoming_data['payment_method_id'], $invoice->payment_method_id);
        $this->assertNull($invoice->last_printed_at);
        $this->assertNull($invoice->last_send_at);
        $this->assertNotNull($invoice->created_at);
        $this->assertNull($invoice->update_at);
        $this->assertNull($invoice->paid_at);
        $this->assertSame((int) $incoming_data['gross_counted'], $invoice->gross_counted);
    }

    /** @test */
    public function store_proforma_first_empty_number_from_proforma_area()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->registry = $this->createInvoiceRegistryForCompany($company);
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_PROFORMA_ENABLED,
            true
        );
        $contractor = factory(Contractor::class, 2)->create([
            'company_id' => $company->id,
        ]);
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type_vat = InvoiceType::findBySlug(InvoiceTypeStatus::VAT);
        $invoice_type_proforma = InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->init_incoming_data(
            $contractor[0],
            $payment_method,
            $invoice_type_vat,
            $company_services,
            $vat_rate
        );

        $incoming_data_proforma = $this->init_incoming_data(
            $contractor[1],
            $payment_method,
            $invoice_type_proforma,
            $company_services,
            $vat_rate
        );

        $initial_invoice_amount = Invoice::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice_vat = Invoice::latest('id')->first();
        $invoice_expect_data = $this->invoiceExpectData();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data_proforma)
            ->assertResponseStatus(201);

        $invoice_proforma = Invoice::latest('id')->first();

        $this->assertSame($initial_invoice_amount + 2, Invoice::count());
        $this->assertSame($this->registry->prefix . '/1/01/2017', $invoice_vat->number);
        $this->assertSame(1, $invoice_vat->order_number);
        $this->assertSame('PRO/' . $this->registry->prefix . '/1/01/2017', $invoice_proforma->number);
        $this->assertSame(1, $invoice_proforma->order_number);
        $this->assertSame($this->registry->id, $invoice_vat->invoice_registry_id);
        $this->assertSame($this->registry->id, $invoice_proforma->invoice_registry_id);
        $this->assertSame($invoice_type_vat->id, $invoice_vat->invoice_type_id);
        $this->assertSame($invoice_type_proforma->id, $invoice_proforma->invoice_type_id);
    }

    /** @test */
    public function store_proforma_no_cashflow_and_no_payment_interaction()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $invoice_registry = $this->createInvoiceRegistryForCompany($company);
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_PROFORMA_ENABLED,
            true
        );
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::CASH);
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $incoming_data['issue_date'] = Carbon::now()->toDateString();
        $incoming_data['payment_term_days'] = 0;
        ModelInvoicePayment::whereRaw('1=1')->delete();
        CashFlow::whereRaw('1=1')->delete();
        InvoiceTaxReport::whereRaw('1=1')->delete();
        $initial_invoice_amount = Invoice::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();
        $this->assertSame(0, CashFlow::count());
        $this->assertSame(0, ModelInvoicePayment::count());
    }

    /** @test */
    public function store_proforma_no_set_sale_document()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $invoice_registry = $this->createInvoiceRegistryForCompany($company);
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_PROFORMA_ENABLED,
            true
        );
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::CASH);
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::PROFORMA);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $incoming_data['issue_date'] = Carbon::now()->toDateString();
        $incoming_data['payment_term_days'] = 0;
        $receipt = factory(Receipt::class)->create(['company_id' => $company->id]);
        $incoming_data['extra_item_id'] = [$receipt->id];
        $incoming_data['extra_item_type'] = 'receipts';

        ModelInvoicePayment::whereRaw('1=1')->delete();
        CashFlow::whereRaw('1=1')->delete();
        InvoiceTaxReport::whereRaw('1=1')->delete();
        $initial_invoice_amount = Invoice::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();
        $this->assertSame(0, CashFlow::count());
        $this->assertSame(0, ModelInvoicePayment::count());
        $this->assertSame(0, InvoiceReceipt::count());

        $this->assertSame($initial_invoice_amount + 1, Invoice::count());
        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($invoice->id, $json_data['id']);
        $this->assertSame($invoice->number, $json_data['number']);
        $this->assertSame($invoice->order_number, $json_data['order_number']);
        $this->assertSame($invoice->invoice_registry_id, $json_data['invoice_registry_id']);
        $this->assertSame($invoice->drawer_id, $json_data['drawer_id']);
        $this->assertSame($invoice->company_id, $json_data['company_id']);
        $this->assertSame($invoice->contractor_id, $json_data['contractor_id']);
        $this->assertNull($invoice->corrected_invoice_id);
        $this->assertNull($invoice->correction_type);
        $this->assertSame($invoice->sale_date, $json_data['sale_date']);
        $this->assertSame($invoice->issue_date, $json_data['issue_date']);
        $this->assertSame($invoice->invoice_type_id, $json_data['invoice_type_id']);
        $this->assertSame(10.1, $json_data['price_net']);
        $this->assertSame(11.11, $json_data['price_gross']);
        $this->assertSame(4.6, $json_data['vat_sum']);
        $this->assertSame(11.11, $json_data['payment_left']);
        $this->assertSame($invoice->payment_method_id, $json_data['payment_method_id']);
        $this->assertNull($json_data['paid_at']);
        $this->assertEquals($invoice->gross_counted, $json_data['gross_counted']);
        $this->assertNull($json_data['last_printed_at']);
        $this->assertNull($json_data['last_send_at']);
        $this->assertEquals($invoice->created_at, $json_data['created_at']);
        $this->assertEquals($invoice->updated_at, $json_data['updated_at']);
    }

    /** @test */
    public function it_allows_to_add_invoice_to_invoice_with_old_sale_date_with_paid_date_in_past()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->registry = $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();

        /** @var PaymentMethod $payment_method */
        $payment_method = app()->make(PaymentMethod::class);
        // we need payment method cash
        $payment_method = $payment_method::findBySlug(PaymentMethodType::CASH);
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        // now let's assume we sold it in past, we we should be allow to choose any payment_term_days
        $incoming_data['sale_date'] = Carbon::parse('2017-01-01')->toDateString();
        $incoming_data['payment_term_days'] = -7;

        $initial_invoice_amount = Invoice::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();
        $invoice_expect_data = $this->invoiceExpectData();

        $this->assertSame($initial_invoice_amount + 1, Invoice::count());
        $this->assertSame($this->registry->prefix . '/1/01/2017', $invoice->number);
        $this->assertSame(1, $invoice->order_number);
        $this->assertSame($this->registry->id, $invoice->invoice_registry_id);
        $this->assertSame($this->user->id, $invoice->drawer_id);
        $this->assertSame($company->id, $invoice->company_id);
        $this->assertNull($invoice->corrected_invoice_id);
        $this->assertNull($invoice->correction_type);
        $this->assertEquals($incoming_data['sale_date'], $invoice->sale_date);
        $this->assertEquals($incoming_data['issue_date'], $invoice->issue_date);
        $this->assertSame($incoming_data['payment_term_days'], $invoice->payment_term_days);
        $this->assertSame($incoming_data['contractor_id'], $invoice->contractor_id);
        $this->assertSame($incoming_data['invoice_type_id'], $invoice->invoice_type_id);
        $this->assertSame($invoice_expect_data['price_net'], $invoice->price_net);
        $this->assertSame($invoice_expect_data['price_gross'], $invoice->price_gross);
        $this->assertSame($invoice_expect_data['vat_sum'], $invoice->vat_sum);
        $this->assertSame(0, $invoice->payment_left);
        $this->assertSame($incoming_data['payment_method_id'], $invoice->payment_method_id);
        $this->assertNull($invoice->last_printed_at);
        $this->assertNull($invoice->last_send_at);
        $this->assertNotNull($invoice->created_at);
        $this->assertNull($invoice->update_at);
        $this->assertEquals(Carbon::parse($incoming_data['issue_date'])
            ->addDays($incoming_data['payment_term_days'])->toDateTimeString(), $invoice->paid_at);
        $this->assertSame((int) $incoming_data['gross_counted'], $invoice->gross_counted);
    }

    /** @test */
    public function store_it_corrected_invoice_was_added_to_database()
    {
        list($company, $invoice, $invoice_items, $incoming_data) = $this->setFinancialEnvironmentForCorrection();

        $initial_invoice_amount = Invoice::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();

        $invoice_expect_data = $this->invoiceExpectDataCorrectionInvoice($invoice_items);

        $this->assertSame($initial_invoice_amount + 1, Invoice::count());
        $this->assertSame('KOR/' . $this->registry->prefix . '/1/01/2017', $invoice->number);
        $this->assertSame(1, $invoice->order_number);
        $this->assertSame($this->registry->id, $invoice->invoice_registry_id);
        $this->assertSame($this->user->id, $invoice->drawer_id);
        $this->assertSame($company->id, $invoice->company_id);
        $this->assertSame($incoming_data['corrected_invoice_id'], $invoice->corrected_invoice_id);
        $this->assertSame($incoming_data['correction_type'], $invoice->correction_type);
        $this->assertEquals('2017-01-15', $invoice->sale_date);
        $this->assertEquals($incoming_data['issue_date'], $invoice->issue_date);
        $this->assertSame($incoming_data['payment_term_days'], $invoice->payment_term_days);
        $this->assertSame($incoming_data['contractor_id'], $invoice->contractor_id);
        $this->assertSame($incoming_data['invoice_type_id'], $invoice->invoice_type_id);
        $this->assertSame($invoice_expect_data['price_net'], $invoice->price_net);
        $this->assertSame($invoice_expect_data['price_gross'], $invoice->price_gross);
        $this->assertSame($invoice_expect_data['vat_sum'], $invoice->vat_sum);
        $this->assertNull($invoice->payment_left);
        $this->assertSame($incoming_data['payment_method_id'], $invoice->payment_method_id);
        $this->assertSame($incoming_data['bank_account_id'], $invoice->bank_account_id);
        $this->assertNull($invoice->last_printed_at);
        $this->assertNull($invoice->last_send_at);
        $this->assertNotNull($invoice->created_at);
        $this->assertNull($invoice->update_at);
        $this->assertNull($invoice->paid_at);
        $this->assertSame((int) $incoming_data['gross_counted'], $invoice->gross_counted);
        $this->assertNull($invoice->description);
    }

    /** @test */
    public function store_it_corrected_invoice_was_copy_invoice_company_data_to_database()
    {
        list($company, $invoice, $invoice_items, $incoming_data) = $this->setFinancialEnvironmentForCorrection();
        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $bank_account = $company->bankAccounts()->save(factory(BankAccount::class)->make());
        array_set($incoming_data, 'payment_method_id', $payment_method->id);
        array_set($incoming_data, 'bank_account_id', $bank_account->id);

        $initial_invoice_amount = Invoice::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);
        $invoice = Invoice::latest('id')->first();

        $this->assertSame($incoming_data['payment_method_id'], $invoice->payment_method_id);
        $this->assertSame($incoming_data['bank_account_id'], $invoice->bank_account_id);
        $this->assertSame($bank_account->bank_name, $invoice->invoiceCompany->bank_name);
        $this->assertSame($bank_account->number, $invoice->invoiceCompany->bank_account_number);
    }

    /** @test */
    public function store_invoice_items_was_added_to_database()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE,
            true
        );
        $invoice_registry = $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $initial_invoice_items_amount = InvoiceItem::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $expect_added_invoice_items = 4;
        $invoice = Invoice::latest('id')->first();
        $invoice_items = InvoiceItem::latest('id')->take($expect_added_invoice_items)->get();
        $invoice_items_expect = $this->invoiceExpectData()['items'];

        $this->assertSame(
            $initial_invoice_items_amount + $expect_added_invoice_items,
            $invoice_items->count()
        );
        $i = $expect_added_invoice_items;

        foreach ($invoice_items as $invoice_item) {
            $company_service = $company_services[--$i]->fresh();
            $this->assertSame($invoice->id, $invoice_item->invoice_id);
            $this->assertSame($company_service->id, $invoice_item->company_service_id);
            $this->assertSame($company_service->name, $invoice_item->name);
            $this->assertSame(1, $company_service->is_used);
            $this->assertSame($invoice_items_expect[$i]['custom_name'], $invoice_item->custom_name);
            $this->assertSame($invoice_items_expect[$i]['price_net'], $invoice_item->price_net);
            $this->assertSame(
                $invoice_items_expect[$i]['price_net_sum'],
                $invoice_item->price_net_sum
            );
            $this->assertNull($invoice_item->price_gross);
            $this->assertSame(
                $invoice_items_expect[$i]['price_gross_sum'],
                $invoice_item->price_gross_sum
            );
            $this->assertEquals($vat_rate->rate, $invoice_item->vat_rate);
            $this->assertSame($vat_rate->id, $invoice_item->vat_rate_id);
            $this->assertSame($invoice_items_expect[$i]['vat_sum'], $invoice_item->vat_sum);
            $this->assertSame($invoice_items_expect[$i]['quantity'], $invoice_item->quantity);
            $this->assertFalse((bool) $invoice_item->is_correction);
            $this->assertNull($invoice_item->position_corrected_id);
            $this->assertSame($this->user->id, $invoice_item->creator_id);
            $this->assertFalse((bool) $invoice_item->editor_id);
            $this->assertNotNull($invoice_item->created_at);
            $this->assertNotNull($invoice_item->updated_at);
        }
    }

    /** @test */
    public function store_invoice_items_was_added_custom_naming_turn_off()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE,
            false
        );
        $invoice_registry = $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        foreach ($incoming_data['items'] as $key => $item) {
            unset($item['custom_name']);
            $incoming_data['items'][$key] = $item;
        }
        $initial_invoice_items_amount = InvoiceItem::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $expect_added_invoice_items = 4;
        $invoice = Invoice::latest('id')->first();
        $invoice_items = InvoiceItem::latest('id')->take($expect_added_invoice_items)->get();
        $invoice_items_expect = $this->invoiceExpectData()['items'];

        $this->assertSame(
            $initial_invoice_items_amount + $expect_added_invoice_items,
            $invoice_items->count()
        );
        $i = $expect_added_invoice_items;
        foreach ($invoice_items as $invoice_item) {
            $this->assertSame($invoice->id, $invoice_item->invoice_id);
            $this->assertSame($company_services[--$i]->id, $invoice_item->company_service_id);
            $this->assertSame($company_services[$i]->name, $invoice_item->name);
            $this->assertNull($invoice_item->custom_name);
            $this->assertSame($invoice_items_expect[$i]['price_net'], $invoice_item->price_net);
            $this->assertSame(
                $invoice_items_expect[$i]['price_net_sum'],
                $invoice_item->price_net_sum
            );
            $this->assertNull($invoice_item->price_gross);
            $this->assertSame(
                $invoice_items_expect[$i]['price_gross_sum'],
                $invoice_item->price_gross_sum
            );
            $this->assertEquals($vat_rate->rate, $invoice_item->vat_rate);
            $this->assertSame($vat_rate->id, $invoice_item->vat_rate_id);
            $this->assertSame($invoice_items_expect[$i]['vat_sum'], $invoice_item->vat_sum);
            $this->assertSame($invoice_items_expect[$i]['quantity'], $invoice_item->quantity);
            $this->assertFalse((bool) $invoice_item->is_correction);
            $this->assertNull($invoice_item->position_corrected_id);
            $this->assertSame($this->user->id, $invoice_item->creator_id);
            $this->assertFalse((bool) $invoice_item->editor_id);
            $this->assertNotNull($invoice_item->created_at);
            $this->assertNotNull($invoice_item->updated_at);
        }
    }

    /** @test */
    public function store_it_corrected_invoice_items_was_added_to_database()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE,
            true
        );
        $invoice_registry = $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type_model = factory(InvoiceType::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $invoice = factory(Invoice::class)->create();
        $invoice->company_id = $company->id;
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->payment_method_id = $payment_method->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->invoice_registry_id = $this->registry->id;
        $invoice->save();
        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[1]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoice->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoice->id;
        $invoice_contractor->save();
        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );

        $initial_invoice_items_amount = InvoiceItem::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = InvoiceItem::latest('id')->first();

        $expect_added_invoice_items = 4;
        $invoice = Invoice::latest('id')->first();
        $invoice_items_expect = $this->invoiceExpectDataCorrectionInvoice($invoice_items)['items'];
        $invoice_items = InvoiceItem::latest('id')->take($expect_added_invoice_items)->get();

        $this->assertSame(
            $initial_invoice_items_amount + $expect_added_invoice_items,
            InvoiceItem::count()
        );
        $i = $expect_added_invoice_items;
        foreach ($invoice_items as $invoice_item) {
            $this->assertSame($invoice->id, $invoice_item->invoice_id);
            $this->assertSame($company_services[--$i]->id, $invoice_item->company_service_id);
            $this->assertSame($company_services[$i]->name, $invoice_item->name);
            $this->assertSame($invoice_items_expect[$i]['custom_name'], $invoice_item->custom_name);
            $this->assertSame($invoice_items_expect[$i]['price_net'], $invoice_item->price_net);
            $this->assertSame(
                $invoice_items_expect[$i]['price_net_sum'],
                $invoice_item->price_net_sum
            );
            $this->assertNull($invoice_item->price_gross);
            $this->assertSame(
                $invoice_items_expect[$i]['price_gross_sum'],
                $invoice_item->price_gross_sum
            );
            $this->assertEquals($vat_rate->rate, $invoice_item->vat_rate);
            $this->assertSame($vat_rate->id, $invoice_item->vat_rate_id);
            $this->assertSame($invoice_items_expect[$i]['vat_sum'], $invoice_item->vat_sum);
            $this->assertSame($invoice_items_expect[$i]['quantity'], $invoice_item->quantity);
            $this->assertTrue((bool) $invoice_item->is_correction);
            $this->assertSame(
                $invoice_items_expect[$i]['position_corrected_id'],
                $invoice_item->position_corrected_id
            );
            $this->assertSame($this->user->id, $invoice_item->creator_id);
            $this->assertFalse((bool) $invoice_item->editor_id);
            $this->assertNotNull($invoice_item->created_at);
            $this->assertNotNull($invoice_item->updated_at);
        }
    }

    /** @test */
    public function store_it_corrected_invoice_items_was_added_to_database_custom_naming_turn_off()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->setAppSettings(
            $company,
            ModuleType::INVOICES_SERVICES_NAME_CUSTOMIZE,
            false
        );
        $invoice_registry = $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type_model = factory(InvoiceType::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $invoice = factory(Invoice::class)->create();
        $invoice->company_id = $company->id;
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->payment_method_id = $payment_method->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->invoice_registry_id = $this->registry->id;
        $invoice->save();
        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[1]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoice->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoice->id;
        $invoice_contractor->save();
        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );

        $initial_invoice_items_amount = InvoiceItem::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = InvoiceItem::latest('id')->first();

        $expect_added_invoice_items = 4;
        $invoice = Invoice::latest('id')->first();
        $invoice_items_expect = $this->invoiceExpectDataCorrectionInvoice($invoice_items)['items'];
        $invoice_items = InvoiceItem::latest('id')->take($expect_added_invoice_items)->get();

        $this->assertSame(
            $initial_invoice_items_amount + $expect_added_invoice_items,
            InvoiceItem::count()
        );
        $i = $expect_added_invoice_items;
        foreach ($invoice_items as $invoice_item) {
            $this->assertSame($invoice->id, $invoice_item->invoice_id);
            $this->assertSame($company_services[--$i]->id, $invoice_item->company_service_id);
            $this->assertSame($company_services[$i]->name, $invoice_item->name);
            $this->assertNull($invoice_item->custom_name);
            $this->assertSame($invoice_items_expect[$i]['price_net'], $invoice_item->price_net);
            $this->assertSame(
                $invoice_items_expect[$i]['price_net_sum'],
                $invoice_item->price_net_sum
            );
            $this->assertNull($invoice_item->price_gross);
            $this->assertSame(
                $invoice_items_expect[$i]['price_gross_sum'],
                $invoice_item->price_gross_sum
            );
            $this->assertEquals($vat_rate->rate, $invoice_item->vat_rate);
            $this->assertSame($vat_rate->id, $invoice_item->vat_rate_id);
            $this->assertSame($invoice_items_expect[$i]['vat_sum'], $invoice_item->vat_sum);
            $this->assertSame($invoice_items_expect[$i]['quantity'], $invoice_item->quantity);
            $this->assertTrue((bool) $invoice_item->is_correction);
            $this->assertSame(
                $invoice_items_expect[$i]['position_corrected_id'],
                $invoice_item->position_corrected_id
            );
            $this->assertSame($this->user->id, $invoice_item->creator_id);
            $this->assertFalse((bool) $invoice_item->editor_id);
            $this->assertNotNull($invoice_item->created_at);
            $this->assertNotNull($invoice_item->updated_at);
        }
    }

    /** @test */
    public function store_invoice_was_added_to_database_gross_counted()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->registry = $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 2)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->init_incoming_data_gross_counted(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $incoming_data['bank_account_id'] = '';

        $initial_invoice_amount = Invoice::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();
        $invoice_expect_data = $this->invoiceExpectData_gross_count();

        $this->assertSame($initial_invoice_amount + 1, Invoice::count());
        $this->assertSame($this->registry->prefix . '/1/01/2017', $invoice->number);
        $this->assertSame(1, $invoice->order_number);
        $this->assertSame($this->registry->id, $invoice->invoice_registry_id);
        $this->assertSame($this->user->id, $invoice->drawer_id);
        $this->assertSame($company->id, $invoice->company_id);
        $this->assertNull($invoice->corrected_invoice_id);
        $this->assertNull($invoice->correction_type);
        $this->assertEquals($incoming_data['sale_date'], $invoice->sale_date);
        $this->assertEquals($incoming_data['issue_date'], $invoice->issue_date);
        $this->assertSame($incoming_data['payment_term_days'], $invoice->payment_term_days);
        $this->assertSame($incoming_data['contractor_id'], $invoice->contractor_id);
        $this->assertSame($incoming_data['invoice_type_id'], $invoice->invoice_type_id);
        $this->assertSame($invoice_expect_data['price_net'], $invoice->price_net);
        $this->assertSame($invoice_expect_data['price_gross'], $invoice->price_gross);
        $this->assertSame($invoice_expect_data['vat_sum'], $invoice->vat_sum);
        $this->assertSame($invoice_expect_data['price_gross'], $invoice->payment_left);
        $this->assertSame($incoming_data['payment_method_id'], $invoice->payment_method_id);
        $this->assertNull($invoice->last_printed_at);
        $this->assertNull($invoice->last_send_at);
        $this->assertNotNull($invoice->created_at);
        $this->assertNull($invoice->update_at);
        $this->assertNull($invoice->paid_at);
        $this->assertSame((int) $incoming_data['gross_counted'], $invoice->gross_counted);

        $invoice_company = InvoiceCompany::latest('id')->first();
        $this->assertNull($invoice_company->bank_name);
        $this->assertNull($invoice_company->bank_account_number);
    }

    /** @test */
    public function store_invoice_items_was_added_to_database_gross_counted()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 2)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data_gross_counted(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $incoming_data['bank_account_id'] = null;

        $initial_invoice_items_amount = InvoiceItem::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $expect_added_invoice_items = 2;
        $invoice = Invoice::latest('id')->first();
        $invoice_items = InvoiceItem::latest('id')->take($expect_added_invoice_items)->get();
        $invoice_items_expect = $this->invoiceExpectData_gross_count()['items'];
        $this->assertSame(
            $initial_invoice_items_amount + $expect_added_invoice_items,
            $invoice_items->count()
        );
        $i = $expect_added_invoice_items;
        foreach ($invoice_items as $key => $invoice_item) {
            $this->assertSame($invoice->id, $invoice_item->invoice_id);
            $this->assertSame($company_services[--$i]->id, $invoice_item->company_service_id);
            $this->assertSame($company_services[$i]->name, $invoice_item->name);
            $this->assertNull($invoice_item->price_net);
            $this->assertSame(
                $invoice_items_expect[$i]['price_net_sum'],
                $invoice_item->price_net_sum
            );
            $this->assertSame($invoice_items_expect[$i]['price_gross'], $invoice_item->price_gross);
            $this->assertSame(
                $invoice_items_expect[$i]['price_gross_sum'],
                $invoice_item->price_gross_sum
            );
            $this->assertEquals($vat_rate->rate, $invoice_item->vat_rate);
            $this->assertSame($vat_rate->id, $invoice_item->vat_rate_id);
            $this->assertSame($invoice_items_expect[$i]['vat_sum'], $invoice_item->vat_sum);
            $this->assertSame($invoice_items_expect[$key]['quantity'], $invoice_item->quantity);
            $this->assertFalse((bool) $invoice_item->is_correction);
            $this->assertNull($invoice_item->position_corrected_id);
            $this->assertSame($this->user->id, $invoice_item->creator_id);
            $this->assertFalse((bool) $invoice_item->editor_id);
            $this->assertNotNull($invoice_item->created_at);
            $this->assertNotNull($invoice_item->updated_at);
        }

        $invoice_company = InvoiceCompany::latest('id')->first();
        $this->assertNull($invoice_company->bank_name);
        $this->assertNull($invoice_company->bank_account_number);
    }

    /** @test */
    public function store_company_data_was_copied()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 2)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data_gross_counted(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $incoming_data['bank_account_id'] = $company->defaultBankAccount()->id;

        $initial_invoice_companies_amount = InvoiceCompany::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice_company = InvoiceCompany::latest('id')->first();
        $invoice = Invoice::latest('id')->first();
        $this->assertSame($initial_invoice_companies_amount + 1, InvoiceCompany::count());
        $this->assertSame($invoice->id, $invoice_company->invoice_id);
        $this->assertSame($company->id, $invoice_company->company_id);
        $this->assertSame($company->vatin, $invoice_company->vatin);
        $this->assertSame($company->email, $invoice_company->email);
        $this->assertSame($company->phone, $invoice_company->phone);
        $this->assertNotNull($invoice_company->bank_name);
        $this->assertNotNull($invoice_company->bank_account_number);
        $this->assertSame($company->main_address_street, $invoice_company->main_address_street);
        $this->assertEquals($company->main_address_number, $invoice_company->main_address_number);
        $this->assertSame($company->main_address_zip_code, $invoice_company->main_address_zip_code);
        $this->assertSame($company->main_address_city, $invoice_company->main_address_city);
        $this->assertSame($company->main_address_country, $invoice_company->main_address_country);
    }

    /** @test */
    public function store_chosen_bank_account_copy_to_company_data()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 2)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data_gross_counted(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        array_set($incoming_data, 'payment_method_id', PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER)->id);
        $default_bank_account = $company->defaultBankAccount();
        array_set($incoming_data, 'bank_account_id', $default_bank_account->id);

        $initial_invoice_companies_amount = InvoiceCompany::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice_company = InvoiceCompany::latest('id')->first();
        $invoice = Invoice::latest('id')->first();
        $this->assertSame($initial_invoice_companies_amount + 1, InvoiceCompany::count());
        $this->assertSame($company->defaultBankAccount()->id, $invoice->bank_account_id);

        $this->assertSame($company->defaultBankAccount()->bank_name, $invoice_company->bank_name);
        $this->assertSame($company->defaultBankAccount()->number, $invoice_company->bank_account_number);
    }

    /** @test */
    public function store_company_data_and_contractor_data_was_copied_after_add_correction_from_corrected_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $invoice_registry = $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type_model = factory(InvoiceType::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $invoice = factory(Invoice::class)->create();
        $invoice->company_id = $company->id;
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->payment_method_id = $payment_method->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->invoice_registry_id = $this->registry->id;
        $invoice->save();
        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[1]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoice->id;
        $invoice_company->vatin = 'vatin_original';
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoice->id;
        $invoice_contractor->vatin = 'vat_original';
        $invoice_contractor->save();
        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );

        $initial_invoice_companies_amount = InvoiceCompany::count();
        $initial_invoice_contractors_amount = InvoiceContractor::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice_company = InvoiceCompany::latest('id')->first();
        $invoice_contractor = InvoiceContractor::latest('id')->first();
        $invoice = Invoice::latest('id')->first();
        $this->assertSame($initial_invoice_companies_amount + 1, InvoiceCompany::count());
        $this->assertSame($invoice->id, $invoice_company->invoice_id);
        $this->assertSame($company->id, $invoice_company->company_id);
        $this->assertSame('vatin_original', $invoice_company->vatin);
        $this->assertSame($initial_invoice_contractors_amount + 1, InvoiceContractor::count());
        $this->assertSame($invoice->id, $invoice_contractor->invoice_id);
        $this->assertSame($contractor->id, $invoice_contractor->contractor_id);
        $this->assertSame('vat_original', $invoice_contractor->vatin);
    }

    /** @test */
    public function store_contractor_data_was_copied()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 2)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data_gross_counted(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $incoming_data['bank_account_id'] = null;

        $initial_invoice_contractor_amount = InvoiceContractor::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice_contractor = InvoiceContractor::latest('id')->first();
        $invoice = Invoice::latest('id')->first();
        $this->assertSame($initial_invoice_contractor_amount + 1, InvoiceContractor::count());
        $this->assertSame($invoice->id, $invoice_contractor->invoice_id);
        $this->assertSame($contractor->id, $invoice_contractor->contractor_id);
        $this->assertSame($contractor->name, $invoice_contractor->name);
        $this->assertSame($contractor->vatin, $invoice_contractor->vatin);
        $this->assertSame($contractor->email, $invoice_contractor->email);
        $this->assertSame($contractor->phone, $invoice_contractor->phone);
        $this->assertSame($contractor->bank_name, $invoice_contractor->bank_name);
        $this->assertSame(
            $contractor->bank_account_number,
            $invoice_contractor->bank_account_number
        );
        $this->assertSame(
            $contractor->main_address_street,
            $invoice_contractor->main_address_street
        );
        $this->assertEquals(
            $contractor->main_address_number,
            $invoice_contractor->main_address_number
        );
        $this->assertSame(
            $contractor->main_address_zip_code,
            $invoice_contractor->main_address_zip_code
        );
        $this->assertSame($contractor->main_address_city, $invoice_contractor->main_address_city);
        $this->assertSame(
            $contractor->main_address_country,
            $invoice_contractor->main_address_country
        );
    }

    /** @test */
    public function store_it_invoice_and_corrected_invoice_were_attached()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $invoice_registry = $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type_model = factory(InvoiceType::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $invoice = factory(Invoice::class)->create();
        $invoice->company_id = $company->id;
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->payment_method_id = $payment_method->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->invoice_registry_id = $this->registry->id;
        $invoice->save();
        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[1]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoice->id;
        $invoice_company->vatin = 'vatin_original';
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoice->id;
        $invoice_contractor->save();
        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);
        $correction = Invoice::latest('id')->first();
        $this->assertSame(1, $correction->invoices->count());
        $this->assertSame($invoice->id, $correction->invoices->first()->id);
    }

    /** @test */
    public function store_taxes_was_added_to_database()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $invoice_registry = $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 2)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $incoming_data = $this->init_incoming_data_gross_counted(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $incoming_data['bank_account_id'] = null;

        $initial_invoice_amount = InvoiceTaxReport::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();

        $this->assertSame($initial_invoice_amount + 2, InvoiceTaxReport::count());
        $invoice_tax_reports = InvoiceTaxReport::latest('id')->take(2)->get();
        $invoice_items_expect = $this->invoiceExpectData_gross_count()['taxes'];
        $i = 2;
        foreach ($invoice_tax_reports as $tax_report) {
            $this->assertSame($invoice->id, $tax_report->invoice_id);
            $this->assertSame($invoice_items_expect[--$i]['price_net'], $tax_report->price_net);
            $this->assertSame($invoice_items_expect[$i]['price_gross'], $tax_report->price_gross);
            $this->assertSame($vat_rate->id, $tax_report->vat_rate_id);
            $this->assertNotNull($tax_report->created_at);
            $this->assertNotNull($tax_report->updated_at);
        }
    }

    /** @test */
    public function store_return_structure_json()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201)
            ->seeJsonStructure([
                'data' => [
                    'id',
                    'number',
                    'order_number',
                    'invoice_registry_id',
                    'drawer_id',
                    'company_id',
                    'contractor_id',
                    'corrected_invoice_id',
                    'correction_type',
                    'sale_date',
                    'issue_date',
                    'invoice_type_id',
                    'price_net',
                    'price_gross',
                    'vat_sum',
                    'payment_left',
                    'payment_term_days',
                    'payment_method_id',
                    'paid_at',
                    'gross_counted',
                    'description',
                    'last_printed_at',
                    'last_send_at',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    /** @test */
    public function store_return_correct_json()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $invoice_payments_count = ModelInvoicePayment::count();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();

        $this->assertSame($invoice_payments_count, ModelInvoicePayment::count());

        $json_data = $this->decodeResponseJson()['data'];
        $this->assertSame($invoice->id, $json_data['id']);
        $this->assertSame($invoice->number, $json_data['number']);
        $this->assertSame($invoice->order_number, $json_data['order_number']);
        $this->assertSame($invoice->invoice_registry_id, $json_data['invoice_registry_id']);
        $this->assertSame($invoice->drawer_id, $json_data['drawer_id']);
        $this->assertSame($invoice->company_id, $json_data['company_id']);
        $this->assertSame($invoice->contractor_id, $json_data['contractor_id']);
        $this->assertSame($invoice->corrected_invoice_id, $json_data['corrected_invoice_id']);
        $this->assertSame($invoice->correction_type, $json_data['correction_type']);
        $this->assertSame($invoice->sale_date, $json_data['sale_date']);
        $this->assertSame($invoice->issue_date, $json_data['issue_date']);
        $this->assertSame($invoice->invoice_type_id, $json_data['invoice_type_id']);
        $this->assertSame(10.1, $json_data['price_net']);
        $this->assertSame(11.11, $json_data['price_gross']);
        $this->assertSame(4.6, $json_data['vat_sum']);
        $this->assertSame(11.11, $json_data['payment_left']);
        $this->assertSame($invoice->payment_method_id, $json_data['payment_method_id']);
        $this->assertNull($json_data['paid_at']);
        $this->assertEquals($invoice->gross_counted, $json_data['gross_counted']);
        $this->assertEquals($invoice->description, $json_data['description']);
        $this->assertNull($json_data['last_printed_at']);
        $this->assertNull($json_data['last_send_at']);
        $this->assertEquals($invoice->created_at, $json_data['created_at']);
        $this->assertEquals($invoice->updated_at, $json_data['updated_at']);
        $this->assertArrayNotHasKey('order_number_date', $json_data);
    }

    /** @test */
    public function store_return_correct_json_with_receipt()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $receipt = factory(Receipt::class)->create(['company_id' => $company->id]);
        $incoming_data['extra_item_id'] = [$receipt->id];
        $incoming_data['extra_item_type'] = 'receipts';

        $invoice_payments_count = ModelInvoicePayment::count();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();
        $invoice_receipt = InvoiceReceipt::latest('created_at')->first();

        $json_data = $this->decodeResponseJson()['data'];

        $this->assertSame($invoice_payments_count, ModelInvoicePayment::count());

        $this->assertSame($invoice->id, $invoice_receipt->invoice_id);
        $this->assertSame($receipt->id, $invoice_receipt->receipt_id);

        $this->assertSame($invoice->id, $json_data['id']);
        $this->assertSame($invoice->number, $json_data['number']);
        $this->assertSame($invoice->order_number, $json_data['order_number']);
        $this->assertSame($invoice->invoice_registry_id, $json_data['invoice_registry_id']);
        $this->assertSame($invoice->drawer_id, $json_data['drawer_id']);
        $this->assertSame($invoice->company_id, $json_data['company_id']);
        $this->assertSame($invoice->contractor_id, $json_data['contractor_id']);
        $this->assertSame($invoice->corrected_invoice_id, $json_data['corrected_invoice_id']);
        $this->assertSame($invoice->correction_type, $json_data['correction_type']);
        $this->assertSame($invoice->sale_date, $json_data['sale_date']);
        $this->assertSame($invoice->issue_date, $json_data['issue_date']);
        $this->assertSame($invoice->invoice_type_id, $json_data['invoice_type_id']);
        $this->assertSame(10.1, $json_data['price_net']);
        $this->assertSame(11.11, $json_data['price_gross']);
        $this->assertSame(4.6, $json_data['vat_sum']);
        $this->assertSame(11.11, $json_data['payment_left']);
        $this->assertSame($invoice->payment_method_id, $json_data['payment_method_id']);
        $this->assertNull($json_data['paid_at']);
        $this->assertEquals($invoice->gross_counted, $json_data['gross_counted']);
        $this->assertNull($json_data['last_printed_at']);
        $this->assertNull($json_data['last_send_at']);
        $this->assertEquals($invoice->created_at, $json_data['created_at']);
        $this->assertEquals($invoice->updated_at, $json_data['updated_at']);
    }

    /** @test */
    public function test_store_return_correct_json_with_receipt_and_cash_payment()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);

        PaymentMethod::whereRaw('1 = 1')->delete();

        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create([
            'slug' => 'gotowka',
        ]);
        factory(PaymentMethod::class)->create([
            'slug' => 'karta',
        ]);
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $now = Carbon::parse('2017-01-05');
        Carbon::setTestNow($now);
        $receipt = factory(Receipt::class)->create([
            'company_id' => $company->id,
            'sale_date' => '2017-01-05 00:00:00',
            'payment_method_id' => $payment_method->id,
        ]);
        $incoming_data['extra_item_id'] = [$receipt->id];
        $incoming_data['extra_item_type'] = 'receipts';
        $incoming_data = array_merge($incoming_data, [
            'issue_date' => $now->toDateString(),
            'payment_term_days' => 0,
        ]);

        $invoice_payments_count = ModelInvoicePayment::count();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();
        $invoice_receipt = InvoiceReceipt::latest('created_at')->first();
        $invoice_payment = ModelInvoicePayment::latest('id')->first();

        $json_data = $this->decodeResponseJson()['data'];

        $this->assertSame($invoice_payments_count + 1, ModelInvoicePayment::count());

        $this->assertEquals($receipt->sale_date, $invoice->paid_at);

        $this->assertEquals($invoice->price_gross, $invoice_payment->amount);
        $this->assertEquals($invoice->paymentMethod->id, $invoice_payment->payment_method_id);
        $this->assertEquals($this->user->id, $invoice_payment->registrar_id);

        $this->assertEquals($invoice->price_gross, $invoice->payments[0]->amount);
        $this->assertEquals($invoice->payment_method_id, $invoice->payments[0]->payment_method_id);
        $this->assertEquals($this->user->id, $invoice->payments[0]->registrar_id);

        $this->assertSame($invoice->id, $invoice_receipt->invoice_id);
        $this->assertSame($receipt->id, $invoice_receipt->receipt_id);

        $this->assertSame($invoice->id, $json_data['id']);
        $this->assertSame($invoice->number, $json_data['number']);
        $this->assertSame($invoice->order_number, $json_data['order_number']);
        $this->assertSame($invoice->invoice_registry_id, $json_data['invoice_registry_id']);
        $this->assertSame($invoice->drawer_id, $json_data['drawer_id']);
        $this->assertSame($invoice->company_id, $json_data['company_id']);
        $this->assertSame($invoice->contractor_id, $json_data['contractor_id']);
        $this->assertSame($invoice->corrected_invoice_id, $json_data['corrected_invoice_id']);
        $this->assertSame($invoice->correction_type, $json_data['correction_type']);
        $this->assertSame($invoice->sale_date, $json_data['sale_date']);
        $this->assertSame($invoice->issue_date, $json_data['issue_date']);
        $this->assertSame($invoice->invoice_type_id, $json_data['invoice_type_id']);
        $this->assertSame(10.1, $json_data['price_net']);
        $this->assertSame(11.11, $json_data['price_gross']);
        $this->assertSame(4.6, $json_data['vat_sum']);
        $this->assertSame(null, $json_data['payment_left']);
        $this->assertSame($invoice->payment_method_id, $json_data['payment_method_id']);
        $this->assertEquals($receipt->sale_date, $json_data['paid_at']);
        $this->assertEquals($invoice->gross_counted, $json_data['gross_counted']);
        $this->assertNull($json_data['last_printed_at']);
        $this->assertNull($json_data['last_send_at']);
        $this->assertEquals($invoice->created_at, $json_data['created_at']);
        $this->assertEquals($invoice->updated_at, $json_data['updated_at']);
    }

    /** @test */
    public function test_store_return_correct_json_with_receipt_and_card_payment()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);

        PaymentMethod::whereRaw('1 = 1')->delete();

        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create([
            'slug' => 'karta',
        ]);
        factory(PaymentMethod::class)->create([
            'slug' => 'gotowka',
        ]);

        $now = Carbon::parse('2017-01-05');
        Carbon::setTestNow($now);
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $receipt = factory(Receipt::class)->create([
            'company_id' => $company->id,
            'sale_date' => '2017-01-05 00:00:00',
            'payment_method_id' => $payment_method->id,
        ]);
        $incoming_data['extra_item_id'] = [$receipt->id];
        $incoming_data['extra_item_type'] = 'receipts';
        $incoming_data = array_merge($incoming_data, [
            'issue_date' => $now->toDateString(),
            'payment_term_days' => 0,
        ]);

        $invoice_payments_count = ModelInvoicePayment::count();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();
        $invoice_receipt = InvoiceReceipt::latest('created_at')->first();
        $invoice_payment = ModelInvoicePayment::latest('id')->first();

        $json_data = $this->decodeResponseJson()['data'];

        $this->assertSame($invoice_payments_count + 1, ModelInvoicePayment::count());

        $this->assertEquals($receipt->sale_date, $invoice->paid_at);

        $this->assertEquals($invoice->price_gross, $invoice_payment->amount);
        $this->assertEquals($invoice->paymentMethod->id, $invoice_payment->payment_method_id);
        $this->assertEquals($this->user->id, $invoice_payment->registrar_id);

        $this->assertEquals($invoice->price_gross, $invoice->payments[0]->amount);
        $this->assertEquals($invoice->payment_method_id, $invoice->payments[0]->payment_method_id);
        $this->assertEquals($this->user->id, $invoice->payments[0]->registrar_id);

        $this->assertSame($invoice->id, $invoice_receipt->invoice_id);
        $this->assertSame($receipt->id, $invoice_receipt->receipt_id);

        $this->assertSame($invoice->id, $json_data['id']);
        $this->assertSame($invoice->number, $json_data['number']);
        $this->assertSame($invoice->order_number, $json_data['order_number']);
        $this->assertSame($invoice->invoice_registry_id, $json_data['invoice_registry_id']);
        $this->assertSame($invoice->drawer_id, $json_data['drawer_id']);
        $this->assertSame($invoice->company_id, $json_data['company_id']);
        $this->assertSame($invoice->contractor_id, $json_data['contractor_id']);
        $this->assertSame($invoice->corrected_invoice_id, $json_data['corrected_invoice_id']);
        $this->assertSame($invoice->correction_type, $json_data['correction_type']);
        $this->assertSame($invoice->sale_date, $json_data['sale_date']);
        $this->assertSame($invoice->issue_date, $json_data['issue_date']);
        $this->assertSame($invoice->invoice_type_id, $json_data['invoice_type_id']);
        $this->assertSame(10.1, $json_data['price_net']);
        $this->assertSame(11.11, $json_data['price_gross']);
        $this->assertSame(4.6, $json_data['vat_sum']);
        $this->assertNull($json_data['payment_left']);
        $this->assertSame($invoice->payment_method_id, $json_data['payment_method_id']);
        $this->assertEquals($receipt->sale_date, $json_data['paid_at']);
        $this->assertEquals($invoice->gross_counted, $json_data['gross_counted']);
        $this->assertNull($json_data['last_printed_at']);
        $this->assertNull($json_data['last_send_at']);
        $this->assertEquals($invoice->created_at, $json_data['created_at']);
        $this->assertEquals($invoice->updated_at, $json_data['updated_at']);
    }

    /** @test */
    public function store_return_correct_json_with_online_sale()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $online_sale = factory(OnlineSale::class)->create([
            'company_id' => $company->id,
            'sale_date' => '2017-01-05 00:00:00',
        ]);
        $incoming_data['extra_item_id'] = [$online_sale->id];
        $incoming_data['extra_item_type'] = 'online_sales';

        $invoice_payments_count = ModelInvoicePayment::count();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice = Invoice::latest('id')->first();
        $invoice_online_sale = InvoiceOnlineSale::latest('created_at')->first();
        $invoice_payment = ModelInvoicePayment::latest('id')->first();

        $json_data = $this->decodeResponseJson()['data'];

        $this->assertSame($invoice_payments_count + 1, ModelInvoicePayment::count());

        $this->assertEquals($online_sale->sale_date, $invoice->paid_at);

        $this->assertEquals($invoice->price_gross, $invoice_payment->amount);
        $this->assertEquals($invoice->paymentMethod->id, $invoice_payment->payment_method_id);
        $this->assertEquals($this->user->id, $invoice_payment->registrar_id);

        $this->assertEquals($invoice->price_gross, $invoice->payments[0]->amount);
        $this->assertEquals($invoice->payment_method_id, $invoice->payments[0]->payment_method_id);
        $this->assertEquals($this->user->id, $invoice->payments[0]->registrar_id);

        $this->assertSame($invoice->id, $invoice_online_sale->invoice_id);
        $this->assertSame($online_sale->id, $invoice_online_sale->online_sale_id);

        $this->assertSame($invoice->id, $json_data['id']);
        $this->assertSame($invoice->number, $json_data['number']);
        $this->assertSame($invoice->order_number, $json_data['order_number']);
        $this->assertSame($invoice->invoice_registry_id, $json_data['invoice_registry_id']);
        $this->assertSame($invoice->drawer_id, $json_data['drawer_id']);
        $this->assertSame($invoice->company_id, $json_data['company_id']);
        $this->assertSame($invoice->contractor_id, $json_data['contractor_id']);
        $this->assertSame($invoice->corrected_invoice_id, $json_data['corrected_invoice_id']);
        $this->assertSame($invoice->correction_type, $json_data['correction_type']);
        $this->assertSame($invoice->sale_date, $json_data['sale_date']);
        $this->assertSame($invoice->issue_date, $json_data['issue_date']);
        $this->assertSame($invoice->invoice_type_id, $json_data['invoice_type_id']);
        $this->assertSame(10.1, $json_data['price_net']);
        $this->assertSame(11.11, $json_data['price_gross']);
        $this->assertSame(4.6, $json_data['vat_sum']);
        $this->assertSame(null, $json_data['payment_left']);
        $this->assertSame($invoice->payment_method_id, $json_data['payment_method_id']);
        $this->assertEquals($online_sale->sale_date, $json_data['paid_at']);
        $this->assertEquals($invoice->gross_counted, $json_data['gross_counted']);
        $this->assertNull($json_data['last_printed_at']);
        $this->assertNull($json_data['last_send_at']);
        $this->assertEquals($invoice->created_at, $json_data['created_at']);
        $this->assertEquals($invoice->updated_at, $json_data['updated_at']);
    }

    /** @test */
    public function store_add_invoice_payment_to_database_for_cash_payments()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $now = Carbon::parse('2017-01-01');
        Carbon::setTestNow($now);
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $incoming_data = array_merge($incoming_data, [
            'payment_method_id' => $payment_method::findBySlug(PaymentMethodType::CASH)->id,
            'issue_date' => '2016-12-22',
            'payment_term_days' => 10,
        ]);

        $init_invoice_payments = InvoicePayment::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertSame($init_invoice_payments + 1, InvoicePayment::count());
        $invoice = Invoice::latest('id')->first();
        $invoice_payment = InvoicePayment::latest('id')->first();
        $this->assertEquals('2017-01-01 00:00:00', $invoice->paid_at);
        $this->assertSame(0, $invoice->payment_left);
        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame(1111, $invoice_payment->amount);
        $this->assertSame(
            $payment_method::findBySlug(PaymentMethodType::CASH)->id,
            $invoice_payment->payment_method_id
        );
        $this->assertSame($this->user->id, $invoice_payment->registrar_id);
    }

    /** @test */
    public function store_add_invoice_payment_to_database_for_payu_payments()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $now = Carbon::parse('2017-01-01');
        Carbon::setTestNow($now);
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $incoming_data = array_merge($incoming_data, [
            'payment_method_id' => $payment_method::findBySlug(PaymentMethodType::PAYU)->id,
            'issue_date' => '2016-12-22',
            'payment_term_days' => 10,
        ]);

        $init_invoice_payments = InvoicePayment::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertSame($init_invoice_payments + 1, InvoicePayment::count());
        $invoice = Invoice::latest('id')->first();
        $invoice_payment = InvoicePayment::latest('id')->first();
        $this->assertEquals('2017-01-01 00:00:00', $invoice->paid_at);
        $this->assertSame(0, $invoice->payment_left);
        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame(1111, $invoice_payment->amount);
        $this->assertSame(
            $payment_method::findBySlug(PaymentMethodType::PAYU)->id,
            $invoice_payment->payment_method_id
        );
        $this->assertSame($this->user->id, $invoice_payment->registrar_id);
    }

    /** @test */
    public function store_add_invoice_payment_to_database_for_card_payments()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $now = Carbon::parse('2017-01-01');
        Carbon::setTestNow($now);
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $incoming_data = array_merge($incoming_data, [
            'payment_method_id' => $payment_method::findBySlug(PaymentMethodType::DEBIT_CARD)->id,
            'issue_date' => $now->toDateString(),
            'payment_term_days' => 0,
        ]);
        $init_invoice_payments = InvoicePayment::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertSame($init_invoice_payments + 1, InvoicePayment::count());
        $invoice = Invoice::latest('id')->first();
        $invoice_payment = InvoicePayment::latest('id')->first();
        $this->assertEquals('2017-01-01 00:00:00', $invoice->paid_at);
        $this->assertSame(0, $invoice->payment_left);
        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame(1111, $invoice_payment->amount);
        $this->assertSame(
            $payment_method::findBySlug(PaymentMethodType::DEBIT_CARD)->id,
            $invoice_payment->payment_method_id
        );
        $this->assertSame($this->user->id, $invoice_payment->registrar_id);
    }

    /** @test */
    public function store_not_add_invoice_payment_to_database_for_no_card_and_no_cash_payments()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $now = Carbon::parse('2017-01-01');
        Carbon::setTestNow($now);
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $bank_account = $company->bankAccounts()->save(factory(BankAccount::class)->make());
        $incoming_data = array_merge($incoming_data, [
            'payment_method_id' => $payment_method::findBySlug(PaymentMethodType::BANK_TRANSFER)->id,
            'issue_date' => $now->toDateString(),
            'payment_term_days' => 11,
            'bank_account_id' => $bank_account->id,
        ]);

        $init_invoice_payments = InvoicePayment::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertSame($init_invoice_payments, InvoicePayment::count());
        $invoice = Invoice::latest('id')->first();
        $this->assertNull($invoice->paid_at);
        $this->assertSame(1111, $invoice->payment_left);
        $this->assertSame(0, $invoice->payments()->count());
    }

    /** @test */
    public function store_add_invoice_payment_for_correction_to_database_for_cash_payments()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $now = Carbon::parse('2017-01-01');
        Carbon::setTestNow($now);
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type_model = factory(InvoiceType::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $invoice = factory(Invoice::class)->create();
        $invoice->company_id = $company->id;
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->payment_method_id = $payment_method->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->invoice_registry_id = $this->registry->id;
        $invoice->save();
        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[1]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoice->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoice->id;
        $invoice_contractor->save();
        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );

        $incoming_data = array_merge($incoming_data, [
            'payment_method_id' => $payment_method::findBySlug(PaymentMethodType::CASH)->id,
            'issue_date' => $now->toDateString(),
            'payment_term_days' => 0,
        ]);

        $init_invoice_payments = InvoicePayment::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertSame($init_invoice_payments + 1, InvoicePayment::count());
        $invoice = Invoice::latest('id')->first();
        $invoice_payment = InvoicePayment::latest('id')->first();
        $this->assertEquals('2017-01-01 00:00:00', $invoice->paid_at);
        $this->assertSame(0, $invoice->payment_left);
        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame(-1111, $invoice_payment->amount);
        $this->assertSame(
            $payment_method::findBySlug(PaymentMethodType::CASH)->id,
            $invoice_payment->payment_method_id
        );
        $this->assertSame($this->user->id, $invoice_payment->registrar_id);
    }

    /** @test */
    public function store_add_KP_to_database_for_card_payments()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $now = Carbon::parse('2017-01-01');
        Carbon::setTestNow($now);
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $incoming_data = array_merge($incoming_data, [
            'payment_method_id' => $payment_method::findBySlug(PaymentMethodType::DEBIT_CARD)->id,
            'issue_date' => $now->toDateString(),
            'payment_term_days' => 0,
        ]);
        $init_invoice_cash_flow = CashFlow::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertSame($init_invoice_cash_flow + 1, CashFlow::count());
        $invoice = Invoice::latest('id')->first();
        $cash_flow = CashFlow::latest('id')->first();
        $this->assertSame(1, $invoice->cashFlows()->count());
        $this->assertSame($company->id, $cash_flow->company_id);
        $this->assertSame($this->user->id, $cash_flow->user_id);
        $this->assertSame($invoice->id, $cash_flow->invoice_id);
        $this->assertNull($cash_flow->receipt_id);
        $this->assertSame(1111, $cash_flow->amount);
        $this->assertSame('2017-01-01', $cash_flow->flow_date);
        $this->assertEmpty($cash_flow->description);
        $this->assertSame(CashFlow::DIRECTION_IN, $cash_flow->direction);
        $this->assertSame(1, $cash_flow->cashless);
        $this->assertNotNULL($cash_flow->created_at);
    }

    /** @test */
    public function store_add_KP_to_database_for_cash_payments()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $now = Carbon::parse('2017-01-01');
        Carbon::setTestNow($now);
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $incoming_data = array_merge($incoming_data, [
            'payment_method_id' => $payment_method::findBySlug(PaymentMethodType::CASH)->id,
            'issue_date' => $now->toDateString(),
            'payment_term_days' => 0,
        ]);
        $init_invoice_cash_flow = CashFlow::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertSame($init_invoice_cash_flow + 1, CashFlow::count());
        $invoice = Invoice::latest('id')->first();
        $cash_flow = CashFlow::latest('id')->first();
        $this->assertSame(1, $invoice->cashFlows()->count());
        $this->assertSame($company->id, $cash_flow->company_id);
        $this->assertSame($this->user->id, $cash_flow->user_id);
        $this->assertSame($invoice->id, $cash_flow->invoice_id);
        $this->assertNull($cash_flow->receipt_id);
        $this->assertSame(1111, $cash_flow->amount);
        $this->assertSame('2017-01-01', $cash_flow->flow_date);
        $this->assertEmpty($cash_flow->description);
        $this->assertSame(CashFlow::DIRECTION_IN, $cash_flow->direction);
        $this->assertSame(0, $cash_flow->cashless);
        $this->assertNotNULL($cash_flow->created_at);
    }

    /** @test */
    public function store_add_KW_to_database_for_correction_for_card_payments()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $now = Carbon::parse('2017-01-01');
        Carbon::setTestNow($now);
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type_model = factory(InvoiceType::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $invoice = factory(Invoice::class)->create();
        $invoice->company_id = $company->id;
        $invoice->invoice_type_id = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
        $invoice->payment_method_id = $payment_method->id;
        $invoice->contractor_id = $contractor->id;
        $invoice->invoice_registry_id = $this->registry->id;
        $invoice->save();
        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[1]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $invoice_company = factory(InvoiceCompany::class)->create();
        $invoice_company->invoice_id = $invoice->id;
        $invoice_company->save();
        $invoice_contractor = factory(InvoiceContractor::class)->create();
        $invoice_contractor->invoice_id = $invoice->id;
        $invoice_contractor->save();
        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );
        $incoming_data = array_merge($incoming_data, [
            'payment_method_id' => $payment_method::findBySlug(PaymentMethodType::DEBIT_CARD)->id,
            'issue_date' => $now->toDateString(),
            'payment_term_days' => 0,
        ]);
        $init_invoice_cash_flow = CashFlow::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertSame($init_invoice_cash_flow + 1, CashFlow::count());
        $invoice = Invoice::latest('id')->first();
        $cash_flow = CashFlow::latest('id')->first();
        $this->assertSame(1, $invoice->cashFlows()->count());
        $this->assertSame($company->id, $cash_flow->company_id);
        $this->assertSame($this->user->id, $cash_flow->user_id);
        $this->assertSame($invoice->id, $cash_flow->invoice_id);
        $this->assertNull($cash_flow->receipt_id);
        $this->assertSame(1111, $cash_flow->amount);
        $this->assertSame('2017-01-01', $cash_flow->flow_date);
        $this->assertEmpty($cash_flow->description);
        $this->assertSame(CashFlow::DIRECTION_OUT, $cash_flow->direction);
        $this->assertSame(1, $cash_flow->cashless);
        $this->assertNotNULL($cash_flow->created_at);
    }

    /** @test */
    public function store_not_add_KP_to_database_for_no_card_and_no_cash_payments()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $now = Carbon::parse('2017-01-01');
        Carbon::setTestNow($now);
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $bank_account = $company->bankAccounts()->save(factory(BankAccount::class)->make());
        $incoming_data = array_merge($incoming_data, [
            'payment_method_id' => $payment_method::findBySlug(PaymentMethodType::OTHER)->id,
            'issue_date' => $now->toDateString(),
            'payment_term_days' => 0,
            'bank_account_id' => $bank_account->id,
        ]);

        $init_invoice_cash_flow = CashFlow::count();
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertSame($init_invoice_cash_flow, CashFlow::count());
        $invoice = Invoice::latest('id')->first();
        $this->assertSame(0, $invoice->cashFlows()->count());
    }

    /** @test */
    public function store_check_adding_description_to_DB()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        $company_services[0]->print_on_invoice = true;
        $company_services[0]->description = 'Some description';
        $company_services[0]->save();

        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );

        $invoice_payments_count = ModelInvoicePayment::count();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $invoice_items = Invoice::latest('id')->first()->items;
        $this->assertEquals(1, $invoice_items[0]->print_on_invoice);
        $this->assertEquals('Some description', $invoice_items[0]->description);
        $this->assertEquals(0, $invoice_items[1]->print_on_invoice);
        $this->assertNull($invoice_items[1]->description);
        $this->assertEquals(0, $invoice_items[2]->print_on_invoice);
        $this->assertNull($invoice_items[2]->description);
        $this->assertEquals(0, $invoice_items[3]->print_on_invoice);
        $this->assertNull($invoice_items[3]->description);
    }

    /** @test */
    public function store_check_adding_partial_payment()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $bank_transfer = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $cash = PaymentMethod::findBySlug(PaymentMethodType::CASH);
        $payment_method = $bank_transfer;
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        $company_services[0]->print_on_invoice = true;
        $company_services[0]->description = 'Some description';
        $company_services[0]->save();

        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $bank_account = $company->bankAccounts()->save(factory(BankAccount::class)->make());

        $partial_payment = [
            'amount' => 5,
            'payment_method_id' => $cash->id,
        ];
        $incoming_data['special_payment'] = $partial_payment;
        $incoming_data['bank_account_id'] = $bank_account->id;

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $data = $this->response->getData()->data;
        $this->assertEquals(6.11, $data->payment_left);
        $this->assertEquals($bank_transfer->id, $data->payment_method_id);

        $invoice = Invoice::find($data->id);
        $this->assertCount(1, $invoice->payments);
        $payment = $invoice->payments->first();
        $this->assertEquals($data->id, $payment->invoice_id);
        $this->assertEquals(500, $payment->amount);
        $this->assertEquals($cash->id, $payment->payment_method_id);
        $this->assertEquals($this->user->id, $payment->registrar_id);
        $this->assertEquals(1, $payment->special_partial_payment);
    }

    /** @test */
    public function store_partial_payment_amount_same_as_invoice_price_gross_throw_error()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $bank_transfer = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $cash = PaymentMethod::findBySlug(PaymentMethodType::CASH);
        $payment_method = $bank_transfer;
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        $company_services[0]->print_on_invoice = true;
        $company_services[0]->description = 'Some description';
        $company_services[0]->save();

        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $partial_payment = [
            'amount' => 11.11,
            'payment_method_id' => $cash->id,
        ];
        $incoming_data['special_payment'] = $partial_payment;

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'special_payment.amount',
        ]);
    }

    /** @test */
    public function store_check_two_special_partial_payments()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $card = PaymentMethod::findBySlug(PaymentMethodType::DEBIT_CARD);
        $cash = PaymentMethod::findBySlug(PaymentMethodType::CASH);
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create();
        $company_services[0]->print_on_invoice = true;
        $company_services[0]->description = 'Some description';
        $company_services[0]->save();

        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $card,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $partial_payment = [
            'amount' => 5,
            'payment_method_id' => $cash->id,
        ];
        $incoming_data['special_payment'] = $partial_payment;
        $incoming_data['payment_term_days'] = 0;
        $incoming_data['issue_date'] = Carbon::now()->toDateString();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $data = $this->response->getData()->data;
        $this->assertEquals(0, $data->payment_left);
        $this->assertEquals($card->id, $data->payment_method_id);

        $invoice = Invoice::find($data->id);
        $this->assertNull($invoice->bank_account_id);
        $this->assertNull($invoice->invoiceCompany->bank_name);
        $this->assertNull($invoice->invoiceCompany->bank_account_number);

        $this->assertCount(2, $invoice->payments);
        $payments = $invoice->payments->sortBy('id');

        // First partial payment
        $this->assertEquals($data->id, $payments[0]->invoice_id);
        $this->assertEquals(500, $payments[0]->amount);
        $this->assertEquals($cash->id, $payments[0]->payment_method_id);
        $this->assertEquals($this->user->id, $payments[0]->registrar_id);
        $this->assertEquals(1, $payments[0]->special_partial_payment);
        // Second partial payment
        $this->assertEquals($data->id, $payments[1]->invoice_id);
        $this->assertEquals(611, $payments[1]->amount);
        $this->assertEquals($card->id, $payments[1]->payment_method_id);
        $this->assertEquals($this->user->id, $payments[1]->registrar_id);
        $this->assertEquals(1, $payments[1]->special_partial_payment);
    }

    /** @test */
    public function store_check_adding_pkwiu_to_invoice_items()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $bank_transfer = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $cash = PaymentMethod::findBySlug(PaymentMethodType::CASH);
        $payment_method = $bank_transfer;
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create([
            'pkwiu' => '11.11.11.1',
        ]);

        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->type = CompanyService::TYPE_ARTICLE;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $bank_account = $company->bankAccounts()->save(factory(BankAccount::class)->make());

        $partial_payment = [
            'amount' => 5,
            'payment_method_id' => $cash->id,
        ];
        $incoming_data['special_payment'] = $partial_payment;
        $incoming_data['bank_account_id'] = $bank_account->id;

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $data = $this->response->getData()->data;
        $this->assertEquals(6.11, $data->payment_left);
        $this->assertEquals($bank_transfer->id, $data->payment_method_id);

        $invoice = Invoice::find($data->id);
        $this->assertCount(4, $invoice->items);
        foreach ($invoice->items->pluck('pkwiu') as $item) {
            $this->assertEquals($item, '11.11.11.1');
        }

        // Check if InvoiceItems have 'article' value in type attribute
        foreach (InvoiceItem::all() as $item) {
            $this->assertEquals(CompanyService::TYPE_ARTICLE, $item->type);
        }
    }

    /** @test */
    public function store_check_storing_country_vatin_prefix_id_in_invoice_contractor_and_invoice_companies()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $pl_vatin_prefix_id = CountryVatinPrefix::where('name', 'Polska')->first()->id;
        $company->country_vatin_prefix_id = $pl_vatin_prefix_id;
        $company->save();
        $contractor = factory(Contractor::class)->create([
            'company_id' => $company->id,
            'country_vatin_prefix_id' => $pl_vatin_prefix_id,
        ]);
        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create([
            'pkwiu' => '11.11.11.1',
        ]);

        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }

        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $incoming_data['bank_account_id'] = $company->defaultBankAccount()->id;

        $this->count(0, InvoiceContractor::all());

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertCount(1, InvoiceContractor::all());
        $this->assertEquals(
            $pl_vatin_prefix_id,
            InvoiceContractor::first()->country_vatin_prefix_id
        );
        $this->assertCount(1, InvoiceCompany::all());
        $this->assertEquals(
            $pl_vatin_prefix_id,
            InvoiceCompany::first()->country_vatin_prefix_id
        );
    }

    /** @test */
    public function store_check_marking_contractor_as_used()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $invoice_type = factory(InvoiceType::class)->create([
            'slug' => InvoiceTypeStatus::VAT,
        ]);
        $company_services = factory(CompanyService::class, 4)->create([
            'pkwiu' => '11.11.11.1',
        ]);

        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();
        $incoming_data = $this->init_incoming_data(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate
        );
        $incoming_data['bank_account_id'] = $company->defaultBankAccount()->id;

        $this->assertCount(0, Invoice::all());
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(201);

        $this->assertCount(1, Invoice::all());
        $invoice = Invoice::first();
        $this->assertEquals(1, $invoice->contractor->is_used);
    }

    /** @test */
    public function store_correction_with_different_registry_id_then_corrected_invoice_get_error()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $this->registry->company_id = $company->id;
        $this->registry->save();
        $this->createInvoiceRegistryForCompany($company);
        $contractor = factory(Contractor::class)->create();
        $contractor->company_id = $company->id;
        $contractor->save();
        $payment_method = factory(PaymentMethod::class)->create();
        $invoice_type = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $company_services = factory(CompanyService::class, 4)->create();
        foreach ($company_services as $service) {
            $service->company_id = $company->id;
            $service->save();
        }
        $vat_rate = factory(VatRate::class)->create();
        $vat_rate->rate = 10;
        $vat_rate->save();

        $delivery_address = factory(ContractorAddress::class)->create([
            'contractor_id' => $contractor->id,
        ]);
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
            'payment_method_id' => $payment_method->id,
            'contractor_id' => $contractor->id,
            'delivery_address_id' => $delivery_address->id,
            'invoice_registry_id' => $this->registry->id,
        ]);
        $invoice_items = factory(InvoiceItem::class, 4)->create();
        $invoice_items[0]->invoice_id = $invoice->id;
        $invoice_items[0]->company_service_id = $company_services[0]->id;
        $invoice_items[0]->save();
        $invoice_items[1]->invoice_id = $invoice->id;
        $invoice_items[1]->company_service_id = $company_services[1]->id;
        $invoice_items[1]->save();
        $invoice_items[2]->invoice_id = $invoice->id;
        $invoice_items[2]->company_service_id = $company_services[2]->id;
        $invoice_items[2]->save();
        $invoice_items[3]->invoice_id = $invoice->id;
        $invoice_items[3]->company_service_id = $company_services[3]->id;
        $invoice_items[3]->save();
        $invoice_company = factory(InvoiceCompany::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        $invoice_contractor = factory(InvoiceContractor::class)->create([
            'invoice_id' => $invoice->id,
        ]);
        $incoming_data = $this->init_incoming_data_correction_invoice(
            $contractor,
            $payment_method,
            $invoice_type,
            $company_services,
            $vat_rate,
            $invoice,
            $invoice_items
        );
        $initial_delivery_addresses = InvoiceDeliveryAddress::count();
        $this->setAppSettings($company, ModuleType::INVOICES_ADDRESSES_DELIVERY_ENABLED, true);
        unset($incoming_data['delivery_address_id'], $incoming_data['default_delivery']);
        $new_test_reg_id = factory(InvoiceRegistry::class)->create(['company_id' => $company->id]);
        $incoming_data['invoice_registry_id'] = $new_test_reg_id->id;
        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'invoice_registry_id',
        ]);
    }

    /** @test */
    public function store_expect_exception_when_issue_date_before_month_of_number_order()
    {
        $this->withoutExceptionHandling();
        $this->expectException(\PDOException::class);
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $this->registry->company_id = $company->id;
        $this->registry->prefix = 'TEST';
        $this->registry->save();

        factory(Invoice::class)->create([
            'invoice_registry_id' => $this->registry->id,
            'issue_date' => Carbon::parse('2017-05-31'),
            'number' => 'TEST/1/06/2017',
            'order_number' => 1,
            'company_id' => $company->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $incoming_data['issue_date'] = Carbon::parse('2017-06-30')->toDateString();
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data);
    }

    /** @test */
    public function store_set_order_number_date_by_issue_date()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();

        $issue_date = Carbon::parse('2017-06-30')->toDateString();
        $sale_date = '2017-05-31';

        $incoming_data['issue_date'] = $issue_date;
        $incoming_data['sale_date'] = $sale_date;

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data);

        $invoice = Invoice::latest('id')->first();

        $this->assertSame($issue_date, $invoice->order_number_date);
        $this->assertSame($issue_date, $invoice->issue_date);
        $this->assertSame($sale_date, $invoice->sale_date);
    }

    /** @test */
    public function store_set_order_number_according_with_order_number_date()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();

        $issue_date = Carbon::parse('2017-06-30')->toDateString();

        factory(Invoice::class)->create([
            'invoice_registry_id' => $this->registry->id,
            'order_number_date' => Carbon::parse('2017-06-01'),
            'issue_date' => Carbon::parse('2017-05-31'),
            'number' => '1/06/2017',
            'order_number' => 1,
            'company_id' => $company->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id,
        ]);
        $issue_date = Carbon::parse('2017-06-30')->toDateString();

        $incoming_data['issue_date'] = $issue_date;
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data);

        $invoice = Invoice::latest('id')->first();

        $this->assertSame(2, $invoice->order_number);
        $this->assertSame($this->registry->prefix . '/2/06/2017', $invoice->number);
    }

    /** @test */
    public function store_check_adding_service_unit_to_invoice_items()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data);

        $invoice_items = Invoice::latest('id')->first()->items;

        foreach ($invoice_items as $item) {
            $this->assertEquals('kilogram', $item->serviceUnit->name);
        }
    }

    /** @test */
    public function store_check_normalizing_quantity()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data);

        $invoice_items = Invoice::latest('id')->first()->items;

        $this->assertEquals(1000, $invoice_items[0]->quantity);
        $this->assertEquals(10000, $invoice_items[1]->quantity);
        $this->assertEquals(100000, $invoice_items[2]->quantity);
        $this->assertEquals(1000000, $invoice_items[3]->quantity);
    }

    /** @test */
    public function store_check_decimal_quantity()
    {
        $unit_id = ServiceUnit::where('slug', ServiceUnit::HOUR)->first()->id;
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();
        $incoming_data['items'][0]['quantity'] = 1.234;
        $incoming_data['items'][0]['service_unit_id'] = $unit_id;
        $incoming_data['items'][1]['quantity'] = 1.234;

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->assertResponseStatus(422);

        $this->verifyValidationResponse([
            'items.0.quantity',
        ]);
    }

    /** @test */
    public function store_add_logotype_to_invoice_company()
    {
        File::copy(
            storage_path('phpunit_tests/samples/avatar.jpg'),
            storage_path('logotypes/logotype.jpg')
        );

        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();

        $company->logotype = 'logotype.jpg';
        $company->save();

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data);

        $invoice = Invoice::latest('id')->first();
        $this->assertEquals('logotype.jpg', $invoice->invoiceCompany->logotype);
        Storage::disk('logotypes')->exists('logotype.jpg');
        // Delete test file
        Storage::disk('logotypes')->delete('logotype.jpg');
        Storage::disk('logotypes')->assertAbsent('logotype.jpg');
    }

    /** @test */
    public function store_with_registry_with_start_number()
    {
        list($company, $delivery_address, $incoming_data) = $this->setFinancialEnvironment();

        $year_format = InvoiceFormat::findByFormatStrict(InvoiceFormat::YEARLY_FORMAT);
        $registry = factory(InvoiceRegistry::class)->create([
            'start_number' => 123,
            'company_id' => $company->id,
            'prefix' => '',
            'invoice_format_id' => $year_format->id,
        ]);

        $incoming_data['invoice_registry_id'] = $registry->id;
        $incoming_data['invoice_type_id'] = InvoiceType::findBySlug(InvoiceTypeStatus::VAT)
            ->id;

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->seeStatusCode(201);

        $invoice = Invoice::latest('id')->first();
        $this->assertEquals('123/2017', $invoice->number);
        $this->assertEquals(123, $registry->fresh()->start_number);

        $this->post('invoices?selected_company_id=' . $company->id, $incoming_data)
            ->seeStatusCode(201);

        $invoice = Invoice::latest('id')->first();
        $this->assertEquals('124/2017', $invoice->number);
    }
}
