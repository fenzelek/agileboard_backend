<?php

namespace Tests\Feature\App\Modules\Company\Http\Controllers;

use App\Models\Db\ServiceUnit;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class CompanyServiceUnitControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $this->assignUsersToCompany(User::all(), $company, RoleType::OWNER);

        $units_db = ServiceUnit::all();

        $this->get('companies/service-units')->assertResponseOk();

        $response = $this->decodeResponseJson()['data'];

        $this->assertSame($units_db->count(), count($response));

        collect($response)->each(function ($unit) use ($units_db) {
            $unit_db = $units_db->first(function ($unit_db) use ($unit) {
                return $unit_db->slug == $unit['slug'];
            });
            $this->assertEquals($unit_db->slug, $unit['slug']);
            $this->assertEquals($unit_db->name, $unit['name']);
            $this->assertEquals($unit_db->decimal, $unit['decimal']);
        });
    }

    /** @test */
    public function index_correct_order()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $this->assignUsersToCompany(User::all(), $company, RoleType::OWNER);

        $units_db = ServiceUnit::all();

        $this->get('companies/service-units')->assertResponseOk();

        $response = $this->response->getData()->data;

        $this->assertServiceUnitListIndex(0, ServiceUnit::SERVICE);
        $this->assertServiceUnitListIndex(1, ServiceUnit::UNIT);
        $this->assertServiceUnitListIndex(2, ServiceUnit::METR);
        $this->assertServiceUnitListIndex(3, ServiceUnit::MONTH);
        $this->assertServiceUnitListIndex(4, ServiceUnit::PACKAGE);
    }

    private function assertServiceUnitListIndex($index, $service_slug)
    {
        $service_unit = ServiceUnit::findBySlug($service_slug);
        $json = $this->decodeResponseJson()['data'];
        $this->assertEquals($service_unit->slug, $json[$index]['slug']);
        $this->assertEquals($service_unit->name, $json[$index]['name']);
        $this->assertEquals($service_unit->decimal, $json[$index]['decimal']);
    }
}
