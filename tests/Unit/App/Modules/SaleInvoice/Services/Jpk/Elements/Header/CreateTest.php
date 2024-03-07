<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Header;

use App\Models\Db\Company;
use App\Models\Db\CompanyJpkDetail;
use App\Models\Db\TaxOffice;
use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Header;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CreateTest extends TestCase
{
    /** @test */
    public function it_creates_element_of_valid_type()
    {
        $company = new Company();
        $jpk_detail = new CompanyJpkDetail();
        $jpk_detail->setRelation('taxOffice', new TaxOffice(['code' => 'sample']));
        $company->setRelation('jpkDetail', $jpk_detail);

        $header = new Header();
        $element = $header->create($company, Carbon::now(), Carbon::now());
        $this->assertTrue($element instanceof Element);
    }

    /** @test */
    public function it_creates_element_with_valid_content()
    {
        $now = '2017-02-16 13:23:12';
        $tax_office_code = 'sample_231_code';

        Carbon::setTestNow($now);
        $company = new Company();
        $jpk_detail = new CompanyJpkDetail();
        $jpk_detail->setRelation('taxOffice', new TaxOffice(['code' => $tax_office_code]));
        $company->setRelation('jpkDetail', $jpk_detail);
        $start_date = '2017-01-02';
        $end_date = '2017-12-06';

        $header = new Header();
        $element = $header->create($company, Carbon::parse($start_date), Carbon::parse($end_date));
        $this->assertSame(
            [
            'name' => 'tns:Naglowek',
            'value' => null,
            'attributes' => [],
            'children' => [
                [
                    'name' => 'tns:KodFormularza',
                    'value' => 'JPK_FA',
                    'attributes' => [
                        [
                            'name' => 'kodSystemowy',
                            'value' => 'JPK_FA (1)',
                        ],
                        [
                            'name' => 'wersjaSchemy',
                            'value' => '1-0',
                        ],
                    ],
                    'children' => [],
                ],
                [
                    'name' => 'tns:WariantFormularza',
                    'value' => 1,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:CelZlozenia',
                    'value' => 1,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:DataWytworzeniaJPK',
                    'value' => '2017-02-16T13:23:12',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:DataOd',
                    'value' => $start_date,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:DataDo',
                    'value' => $end_date,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:DomyslnyKodWaluty',
                    'value' => 'PLN',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:KodUrzedu',
                    'value' => $tax_office_code,
                    'attributes' => [],
                    'children' => [],
                ],
            ],

        ],
            $element->toArray()
        );
    }
}
