<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Company;

use App\Models\Db\Company as CompanyModel;
use App\Models\Db\CompanyJpkDetail;
use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Company;
use App\Modules\SaleInvoice\Services\Jpk\Elements\CompanyAddress;
use App\Modules\SaleInvoice\Services\Jpk\Elements\CompanyIdentification;
use Mockery;
use Tests\TestCase;

class CreateTest extends TestCase
{
    /** @test */
    public function it_creates_element_of_valid_type()
    {
        $company = new CompanyModel();
        $company->setRelation('jpkDetail', new CompanyJpkDetail());
        $company_block = app()->make(Company::class);
        $element = $company_block->create($company);
        $this->assertTrue($element instanceof Element);
    }

    /** @test */
    public function it_creates_element_with_valid_content()
    {
        $company = new CompanyModel();
        $company->id = 412312;
        $company->setRelation('jpkDetail', new CompanyJpkDetail());

        $identification_data = new Element('output', 'foo');
        $company_identification = Mockery::mock(CompanyIdentification::class);
        $company_identification->shouldReceive('create')->once()
            ->with(Mockery::on(function ($arg) use ($company) {
                return $arg instanceof CompanyModel && $arg->id == $company->id;
            }))->andReturn($identification_data);

        $company_data = new Element('company_output', 'bar');
        $company_address = Mockery::mock(CompanyAddress::class);
        $company_address->shouldReceive('create')->once()
            ->with(Mockery::on(function ($arg) use ($company) {
                return $arg instanceof CompanyModel && $arg->id == $company->id;
            }))->andReturn($company_data);

        $company_block = new Company($company_identification, $company_address);
        $element = $company_block->create($company);

        $this->assertSame(
            [
            'name' => 'tns:Podmiot1',
            'value' => null,
            'attributes' => [],
            'children' => [
                [
                    'name' => 'output',
                    'value' => 'foo',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'company_output',
                    'value' => 'bar',
                    'attributes' => [],
                    'children' => [],
                ],
            ],
        ],
            $element->toArray()
        );
    }
}
