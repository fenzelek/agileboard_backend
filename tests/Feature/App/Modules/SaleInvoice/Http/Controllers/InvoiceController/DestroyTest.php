<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController;

use App\Models\Db\CashFlow;
use App\Models\Db\Company;
use App\Models\Db\Invoice;
use App\Models\Db\Package;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class DestroyTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function deleted_invoice_by_unauthorized_user_return_404()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::PREMIUM);

        $this->delete('invoices/1?selected_company_id=' . $company->id)
            ->assertResponseStatus(401);
    }

    /** @test */
    public function invoice_id_is_not_numeric_return_422()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();

        $this->delete('invoices/test?selected_company_id=' . $company->id)
            ->assertResponseStatus(422);
    }

    /** @test */
    public function invoice_is_not_assigned_to_user_company_return_422()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $fake_company = factory(Company::class)->create();

        $invoice = factory(Invoice::class)->create([
            'company_id' => $fake_company->id,
        ]);

        $this->delete('invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->assertResponseStatus(422);
    }

    /** @test */
    public function invoice_has_correction_return_424()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);
        $invoice_next = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);

        $invoice->parentInvoices()->save($invoice_next);

        $this->delete('invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->assertResponseStatus(424);
    }

    /** @test */
    public function correct_soft_delete_invoice_with_cash_flow_end_removed_correction_invoice()
    {
        $company = $this->login_user_and_return_company_with_his_employee_role();
        $invoice = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);

        $invoice_next = factory(Invoice::class)->create([
            'company_id' => $company->id,
        ]);

        $invoice->parentInvoices()->save($invoice_next);
        $invoice_next->delete();

        $cash_flow_1 = factory(CashFlow::class)->create();
        $cash_flow_2 = factory(CashFlow::class)->create();
        // cash flow not assigned to the invoice
        factory(CashFlow::class)->create();

        $invoice->cashFlows()->save($cash_flow_1);
        $invoice->cashFlows()->save($cash_flow_2);

        $this->assertEquals(1, Invoice::count());
        $this->assertEquals(3, CashFlow::count());

        $this->delete('invoices/' . $invoice->id . '?selected_company_id=' . $company->id)
            ->assertResponseStatus(204);

        $this->assertEquals(0, Invoice::count());
        $this->assertEquals(2, Invoice::onlyTrashed()->count());

        $this->assertEquals(1, CashFlow::count());
        $this->assertEquals(2, CashFlow::onlyTrashed()->count());
    }

    protected function login_user_and_return_company_with_his_employee_role()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::OWNER);

        return $company;
    }
}
