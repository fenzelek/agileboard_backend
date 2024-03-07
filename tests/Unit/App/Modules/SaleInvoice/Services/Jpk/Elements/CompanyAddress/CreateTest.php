<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\CompanyAddress;

use App\Models\Db\Company;
use App\Models\Db\CompanyJpkDetail;
use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\Elements\CompanyAddress;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CreateTest extends TestCase
{
    /** @test */
    public function it_creates_element_of_valid_type()
    {
        $company = new Company();
        $company->setRelation('jpkDetail', new CompanyJpkDetail());
        $company_address = new CompanyAddress();
        $element = $company_address->create($company);
        $this->assertTrue($element instanceof Element);
    }

    /** @test */
    public function it_creates_element_with_valid_content()
    {
        $now = '2017-02-16 13:23:12';

        Carbon::setTestNow($now);
        $company = new Company();
        $company_jpk_detail = new CompanyJpkDetail([
            'state' => 'jakieś województwo',
            'county' => 'uzupełniony powiat',
            'community' => 'testowa gmina',
            'street' => 'bardzo znana ulica',
            'building_number' => '45A/41',
            'flat_number' => '5612',
            'city' => 'Sample city',
            'zip_code' => '41231',
            'postal' => 'Other city',
        ]);
        $company->setRelation('jpkDetail', $company_jpk_detail);

        $company_address = new CompanyAddress();
        $element = $company_address->create($company);
        $this->assertSame(
            [
            'name' => 'tns:AdresPodmiotu',
            'value' => null,
            'attributes' => [],
            'children' => [
                [
                    'name' => 'etd:KodKraju',
                    'value' => 'PL',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:Wojewodztwo',
                    'value' => $company_jpk_detail->state,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:Powiat',
                    'value' => $company_jpk_detail->county,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:Gmina',
                    'value' => $company_jpk_detail->community,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:Ulica',
                    'value' => $company_jpk_detail->street,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:NrDomu',
                    'value' => $company_jpk_detail->building_number,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:NrLokalu',
                    'value' => $company_jpk_detail->flat_number,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:Miejscowosc',
                    'value' => $company_jpk_detail->city,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:KodPocztowy',
                    'value' => $company_jpk_detail->zip_code,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:Poczta',
                    'value' => $company_jpk_detail->postal,
                    'attributes' => [],
                    'children' => [],
                ],
            ],

        ],
            $element->toArray()
        );
    }

    /** @test */
    public function it_doesnt_add_flat_number_when_it_sempty()
    {
        $now = '2017-02-16 13:23:12';

        Carbon::setTestNow($now);
        $company = new Company();
        $company_jpk_detail = new CompanyJpkDetail([
            'state' => 'jakieś województwo',
            'county' => 'uzupełniony powiat',
            'community' => 'testowa gmina',
            'street' => 'bardzo znana ulica',
            'building_number' => '45A/41',
            'flat_number' => '',
            'city' => 'Sample city',
            'zip_code' => '41231',
            'postal' => 'Other city',
        ]);
        $company->setRelation('jpkDetail', $company_jpk_detail);

        $company_address = new CompanyAddress();
        $element = $company_address->create($company);
        $this->assertSame(
            [
            'name' => 'tns:AdresPodmiotu',
            'value' => null,
            'attributes' => [],
            'children' => [
                [
                    'name' => 'etd:KodKraju',
                    'value' => 'PL',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:Wojewodztwo',
                    'value' => $company_jpk_detail->state,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:Powiat',
                    'value' => $company_jpk_detail->county,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:Gmina',
                    'value' => $company_jpk_detail->community,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:Ulica',
                    'value' => $company_jpk_detail->street,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:NrDomu',
                    'value' => $company_jpk_detail->building_number,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:Miejscowosc',
                    'value' => $company_jpk_detail->city,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:KodPocztowy',
                    'value' => $company_jpk_detail->zip_code,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:Poczta',
                    'value' => $company_jpk_detail->postal,
                    'attributes' => [],
                    'children' => [],
                ],
            ],

        ],
            $element->toArray()
        );
    }

    /** @test */
    public function it_doesnt_add_building_number_when_it_is_empty()
    {
        $now = '2017-02-16 13:23:12';

        Carbon::setTestNow($now);
        $company = new Company();
        $company_jpk_detail = new CompanyJpkDetail([
            'state' => 'jakieś województwo',
            'county' => 'uzupełniony powiat',
            'community' => 'testowa gmina',
            'street' => 'bardzo znana ulica',
            'building_number' => '',
            'flat_number' => '5612',
            'city' => 'Sample city',
            'zip_code' => '41231',
            'postal' => 'Other city',
        ]);
        $company->setRelation('jpkDetail', $company_jpk_detail);

        $company_address = new CompanyAddress();
        $element = $company_address->create($company);
        $this->assertSame(
            [
            'name' => 'tns:AdresPodmiotu',
            'value' => null,
            'attributes' => [],
            'children' => [
                [
                    'name' => 'etd:KodKraju',
                    'value' => 'PL',
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:Wojewodztwo',
                    'value' => $company_jpk_detail->state,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:Powiat',
                    'value' => $company_jpk_detail->county,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:Gmina',
                    'value' => $company_jpk_detail->community,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:Ulica',
                    'value' => $company_jpk_detail->street,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:NrLokalu',
                    'value' => $company_jpk_detail->flat_number,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:Miejscowosc',
                    'value' => $company_jpk_detail->city,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:KodPocztowy',
                    'value' => $company_jpk_detail->zip_code,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'etd:Poczta',
                    'value' => $company_jpk_detail->postal,
                    'attributes' => [],
                    'children' => [],
                ],
            ],

        ],
            $element->toArray()
        );
    }
}
