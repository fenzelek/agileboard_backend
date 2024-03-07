<?php

namespace Tests\Feature\App\Modules\Sale\Http\Controllers;

use App\Models\Db\VatRate;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class VatRateControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * This test is for checking API response structure.
     */
    public function testIndex()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        VatRate::whereRaw('1 = 1')->delete();
        factory(VatRate::class, 3)->create();

        $this->get('/vat-rates?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'data' => [['id', 'rate', 'name', 'is_visible', 'created_at', 'updated_at']],
            ])->isJson();
    }

    /**
     * This test is for checking API response data.
     */
    public function testIndexWithData()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        VatRate::whereRaw('1 = 1')->delete();
        $vat_rates = factory(VatRate::class, 3)->create();

        $this->get('/vat-rates?selected_company_id=' . $company->id)
            ->seeStatusCode(200)
            ->isJson();

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals($vat_rates->count(), count($data));

        foreach ($data as $key => $item) {
            $this->assertEquals($vat_rates[$key]->rate, $item['rate']);
            $this->assertEquals($vat_rates[$key]->name, $item['name']);
            $this->assertEquals($vat_rates[$key]->is_visible, $item['is_visible']);
        }
    }

    /** @test */
    public function get_only_NP_rate_for_non_vat_payer()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        $company->update([
            'vat_payer' => false,
        ]);

        $this->get('/vat-rates?selected_company_id=' . $company->id);

        $data = $this->decodeResponseJson()['data'];

        $this->assertEquals(1, count($data));

        $this->assertEquals(0, $data[0]['rate']);
        $this->assertEquals(VatRate::NP, $data[0]['name']);
        $this->assertTrue((bool) $data[0]['is_visible']);
    }
}
