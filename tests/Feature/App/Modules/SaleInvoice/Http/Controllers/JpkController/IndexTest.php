<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\JpkController;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\CompanyJpkDetail;
use App\Models\Db\Invoice;
use App\Models\Db\Package;
use App\Models\Other\RoleType;
use App\Modules\SaleInvoice\Services\Jpk\InvoicesSelector;
use App\Modules\SaleInvoice\Services\Jpk\JpkGenerator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function owner_has_access()
    {
        $this->verifyForRole(RoleType::OWNER);
    }

    /** @test */
    public function admin_has_access()
    {
        $this->verifyForRole(RoleType::ADMIN);
    }

    /** @test */
    public function dealer_has_no_access()
    {
        $this->verifyNoAccessForRole(RoleType::DEALER);
    }

    /** @test */
    public function developer_has_no_access()
    {
        $this->verifyNoAccessForRole(RoleType::DEVELOPER);
    }

    /** @test */
    public function client_has_no_access()
    {
        $this->verifyNoAccessForRole(RoleType::CLIENT);
    }

    /** @test */
    public function employee_has_access()
    {
        $this->verifyForRole(RoleType::EMPLOYEE);
    }

    /** @test */
    public function tax_office_has_access()
    {
        $this->verifyForRole(RoleType::TAX_OFFICE);
    }

    /** @test */
    public function company_with_other_package_gets_error()
    {
        $company = $this->createUserAndCompanyAndLoginUser(RoleType::OWNER);
        $response = $this->get('invoices/jpk/fa/?selected_company_id=' . $company->id .
            '&start_date=2017-01-01&end_date=2017-08-12');
        $this->verifyResponseError($response, 409, ErrorCode::SALE_INVOICE_JPK_NOT_ENABLED);
    }

    /** @test */
    public function company_without_jpk_details_gets_error()
    {
        $company = $this->createUserAndCompanyAndLoginUser(RoleType::OWNER, Package::PREMIUM);
        $response = $this->get('invoices/jpk/fa/?selected_company_id=' . $company->id .
            '&start_date=2017-01-01&end_date=2017-08-12');
        $this->verifyResponseError($response, 409, ErrorCode::SALE_INVOICE_JPK_DETAILS_MISSING);
    }

    /** @test */
    public function company_with_vat_payer_set_to_null_gets_error()
    {
        $company = $this->createUserAndCompanyAndLoginUser(RoleType::OWNER, Package::PREMIUM);
        $company->vat_payer = null;
        $company->save();

        CompanyJpkDetail::create(['company_id' => $company->id]);

        $response = $this->get('invoices/jpk/fa/?selected_company_id=' . $company->id .
            '&start_date=2017-01-01&end_date=2017-08-12');
        $this->verifyResponseError(
            $response,
            409,
            ErrorCode::SALE_INVOICE_JPK_VAT_PAYER_NOT_FILLED_IN
        );
    }

    /** @test */
    public function it_gets_validation_error_when_dates_are_not_filled_in()
    {
        $company = $this->createUserAndCompanyAndLoginUser(RoleType::OWNER, Package::PREMIUM);
        $response = $this->get('invoices/jpk/fa/?selected_company_id=' . $company->id);

        $this->verifyResponseValidation($response, ['start_date', 'end_date']);
    }

    /** @test */
    public function it_gets_validation_error_when_dates_are_not_valid()
    {
        $company = $this->createUserAndCompanyAndLoginUser(RoleType::OWNER, Package::PREMIUM);
        $response = $this->get('invoices/jpk/fa/?selected_company_id=' . $company->id .
            '&start_date=a&end_date=b');

        $this->verifyResponseValidation($response, ['start_date', 'end_date']);
    }

    /** @test */
    public function it_gets_validation_error_when_end_date_is_before_start_date()
    {
        $company = $this->createUserAndCompanyAndLoginUser(RoleType::OWNER, Package::PREMIUM);
        $response = $this->get('invoices/jpk/fa/?selected_company_id=' . $company->id .
            '&start_date=2017-01-01&end_date=2016-12-31');

        $this->verifyResponseValidation($response, ['end_date']);
    }

    /**
     * @param $role_slug
     *
     * @return \Illuminate\Testing\TestResponse
     */
    protected function verifyForRole($role_slug)
    {
        $company = $this->createUserAndCompanyAndLoginUser($role_slug, Package::PREMIUM);

        CompanyJpkDetail::create(['company_id' => $company->id]);

        $invoice = factory(Invoice::class)->create(['company_id' => $company->id, 'id' => 5123]);

        $start_date = '2017-01-15';
        $end_date = '2017-12-04';
        $file_content = 'sample xml content';
        $file_content_type = 'text/xml';
        $file_name = 'sample_file_name.xml';

        $invoices = new Collection([
            new Invoice(['number' => 323]),
            new Invoice(['number' => 'FA/3433443']),
            new Invoice(['number' => 'XT/2017']),
        ]);

        $invoices_selector = Mockery::mock(InvoicesSelector::class);
        $invoices_selector->shouldReceive('get')->once()
            ->with(Mockery::on(function ($arg) use ($company) {
                return $arg instanceof Company && $arg->id == $company->id;
            }), $start_date, $end_date)->andReturn($invoices);

        $jpk_generator = Mockery::mock(JpkGenerator::class);
        $jpk_generator->shouldReceive('setStartDate')->once()->with($start_date)
            ->andReturn($jpk_generator);
        $jpk_generator->shouldReceive('setEndDate')->once()->with($end_date)
            ->andReturn($jpk_generator);

        $jpk_generator->shouldReceive('getFileContent')->once()
            ->with(Mockery::on(function ($arg) use ($company) {
                return $arg instanceof Company && $arg->id == $company->id;
            }), Mockery::on(function ($arg) use ($invoices) {
                $this->assertTrue($arg instanceof Collection);
                $this->assertSame($invoices->pluck('number')->all(), $arg->pluck('number')->all());

                return true;
            }))->andReturn($file_content);
        $jpk_generator->shouldReceive('getFileContentType')->once()->withNoArgs()
            ->andReturn($file_content_type);
        $jpk_generator->shouldReceive('getFileName')->once()->withNoArgs()
            ->andReturn($file_name);

        app()->instance(JpkGenerator::class, $jpk_generator);
        app()->instance(InvoicesSelector::class, $invoices_selector);

        $response = $this->get('invoices/jpk/fa?selected_company_id=' . $company->id .
            '&start_date=' . $start_date . '&end_date=' . $end_date);
        $response->assertStatus(200);

        $this->assertSame($file_content, $response->getContent());
        $headers = $response->headers->all();
        $this->assertSame($file_content_type . '; charset=UTF-8', $headers['content-type'][0]);
        $this->assertSame('attachment; filename=' . $file_name, $headers['content-disposition'][0]);

        return $response;
    }

    protected function verifyNoAccessForRole($role_slug)
    {
        $company = $this->createUserAndCompanyAndLoginUser($role_slug);

        $response = $this->get('invoices/jpk/fa/?selected_company_id=' . $company->id);
        $this->verifyResponseError($response, 401, ErrorCode::NO_PERMISSION);

        return $response;
    }

    protected function createUserAndCompanyAndLoginUser($role_slug, $package = null)
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage($role_slug, $package);
        auth()->loginUsingId($this->user->id);

        return $company;
    }
}
