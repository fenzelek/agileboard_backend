<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\CompanyIdentification;

use App\Models\Db\Company;
use App\Models\Db\CompanyJpkDetail;
use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\Elements\CompanyIdentification;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CreateTest extends TestCase
{
    /** @test */
    public function it_creates_element_of_valid_type()
    {
        $company = new Company();
        $jpk_detail = new CompanyJpkDetail();
        $company->setRelation('jpkDetail', $jpk_detail);

        $company_identification = new CompanyIdentification();
        $element = $company_identification->create($company);
        $this->assertTrue($element instanceof Element);
    }

    /** @test */
    public function it_creates_element_with_valid_content()
    {
        $now = '2017-02-16 13:23:12';

        Carbon::setTestNow($now);
        $company = new Company();
        $company->vatin = '1232312323';
        $company->name = 'Test name ABC "test231';
        $company->country_vatin_prefix_id = 123123;
        $jpk_detail = new CompanyJpkDetail();
        $company->setRelation('jpkDetail', $jpk_detail);

        $company_identification = new CompanyIdentification();
        $element = $company_identification->create($company);
        $this->assertSame(
            [
            'name' => 'tns:IdentyfikatorPodmiotu',
            'value' => null,
            'attributes' => [],
            'children' => [
                [
                    'name' => 'etd:NIP',
                    'value' => $company->vatin,

                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:PelnaNazwa',
                    'value' => $company->name,
                    'attributes' => [],
                    'children' => [],
                ],
            ],

        ],
            $element->toArray()
        );
    }

    /** @test */
    public function it_adds_regon_field_when_regon_is_filled_in()
    {
        $now = '2017-02-16 13:23:12';

        Carbon::setTestNow($now);
        $company = new Company();
        $company->vatin = '1232312323';
        $company->name = 'Test name ABC "test231';
        $company->country_vatin_prefix_id = 123123;
        $jpk_detail = new CompanyJpkDetail(['regon' => 4123123123]);
        $company->setRelation('jpkDetail', $jpk_detail);

        $company_identification = new CompanyIdentification();
        $element = $company_identification->create($company);
        $this->assertSame(
            [
            'name' => 'tns:IdentyfikatorPodmiotu',
            'value' => null,
            'attributes' => [],
            'children' => [
                [
                    'name' => 'etd:NIP',
                    'value' => $company->vatin,

                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:PelnaNazwa',
                    'value' => $company->name,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:REGON',
                    'value' => $jpk_detail->regon,
                    'attributes' => [],
                    'children' => [],
                ],
            ],

        ],
            $element->toArray()
        );
    }
}
