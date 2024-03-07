<?php

namespace Tests\Feature\App\Modules\User\Http\Controllers;

use App\Models\Db\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use Illuminate\Support\Facades\DB;
use Tests\BrowserKitTestCase;

class RoleControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * This test is for checking API response structure.
     */
    public function testIndex()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->get('/roles')
            ->assertResponseOk()
            ->seeJsonStructure([
                'data' => [['id', 'name']],
            ])->isJson();
    }

    /** @test */
    public function index_response_data_correct()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        Role::whereRaw('1 = 1')->delete();

        $roles = factory(Role::class, 5)->create();
        $this->get('/roles')->assertResponseOk();

        $response_roles = $this->decodeResponseJson()['data'];

        $this->assertSame(count($roles), count($response_roles));

        foreach ($roles as $key => $role) {
            $role = $role->fresh();
            $this->assertSame($role->id, $response_roles[$key]['id']);
            $this->assertSame($role->name, $response_roles[$key]['name']);
            $this->assertSame($role->default, $response_roles[$key]['default']);
        }
    }

    /** @test */
    public function company_success()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Role::whereRaw('1 = 1')->delete();

        $roles = factory(Role::class, 5)->create();

        DB::table('company_role')->insert([
            'company_id' => $company->id,
            'role_id' => $roles[0]->id,
            'created_at' => Carbon::now(),
        ]);

        $this->get('/roles/company?selected_company_id=' . $company->id)
            ->assertResponseOk()
            ->seeJsonStructure([
                'data' => [['id', 'name']],
            ])->isJson();

        $response_roles = $this->decodeResponseJson()['data'];

        $this->assertSame(1, count($response_roles));

        $role = $roles[0]->fresh();
        $this->assertSame($role->id, $response_roles[0]['id']);
        $this->assertSame($role->name, $response_roles[0]['name']);
        $this->assertSame($role->default, $response_roles[0]['default']);
    }

    /** @test */
    public function company_without_selected_company()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::OWNER);
        auth()->loginUsingId($this->user->id);

        Role::whereRaw('1 = 1')->delete();

        $roles = factory(Role::class, 5)->create();

        DB::table('company_role')->insert([
            'company_id' => $company->id,
            'role_id' => $roles[0]->id,
            'created_at' => Carbon::now(),
        ]);

        $this->get('/roles/company')->seeStatusCode(200);

        $response_roles = $this->decodeResponseJson()['data'];

        $this->assertSame(0, count($response_roles));
    }
}
