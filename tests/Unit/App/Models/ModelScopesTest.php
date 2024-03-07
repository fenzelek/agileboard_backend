<?php

namespace Tests\Unit\App\Models;

use App\Models\Db\Invoice;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModelScopesTest extends TestCase
{
    use DatabaseTransactions;

    protected $company;

    public function setUp():void
    {
        parent::setUp();
        $this->createUser();
        $this->company = $this->createCompanyWithRole(RoleType::ADMIN);
        $this->user->setSelectedCompany($this->company->id, Role::findByName(RoleType::ADMIN));
        auth()->loginUsingId($this->user->id);
    }

    /** @test */
    public function it_filter_projects_with_inCompany_scope_using_company_model()
    {
        $projects_in_company = factory(Project::class, 2)->create([
            'company_id' => $this->company->id,
        ])->sortBy('id');
        factory(Project::class)->create([
            'company_id' => (int) $this->company->id + 1,
        ]);
        factory(Project::class)->create([
            'company_id' => 0,
        ]);

        $this->assertCount(4, Project::all());
        $filtered_projects = Project::inCompany($this->company)->orderBy('id')->get();
        $this->assertCount(2, $filtered_projects);

        $this->assertEquals(
            $projects_in_company->pluck('id'),
            $filtered_projects->pluck('id')
        );
    }

    /** @test */
    public function it_filter_invoices_with_inCompany_scope_using_user_model()
    {
        $invoices_in_company = factory(Invoice::class, 2)->create([
            'company_id' => $this->company->id,
        ])->sortBy('id');
        factory(Invoice::class)->create([
            'company_id' => (int) $this->company->id + 1,
        ]);
        factory(Invoice::class)->create([
            'company_id' => 0,
        ]);

        $this->assertCount(4, Invoice::all());
        $filtered_invoices = Invoice::inCompany($this->user)->orderBy('id')->get();
        $this->assertCount(2, $filtered_invoices);

        $this->assertEquals(
            $invoices_in_company->pluck('id'),
            $filtered_invoices->pluck('id')
        );
    }

    /** @test */
    public function it_filter_projects_with_companyId_scope()
    {
        $projects_in_company = factory(Project::class, 2)->create([
            'company_id' => $this->company->id,
        ])->sortBy('id');
        factory(Project::class)->create([
            'company_id' => (int) $this->company->id + 1,
        ]);
        factory(Project::class)->create([
            'company_id' => 0,
        ]);

        $this->assertCount(4, Project::all());
        $filtered_projects = Project::companyId($this->company->id)->orderBy('id')->get();
        $this->assertCount(2, $filtered_projects);

        $this->assertEquals(
            $projects_in_company->pluck('id'),
            $filtered_projects->pluck('id')
        );
    }

    /** @test */
    public function it_filter_invoices_with_companyId_scope()
    {
        $invoices_in_company = factory(Invoice::class, 2)->create([
            'company_id' => $this->company->id,
        ])->sortBy('id');
        factory(Invoice::class)->create([
            'company_id' => (int) $this->company->id + 1,
        ]);
        factory(Invoice::class)->create([
            'company_id' => 0,
        ]);

        $this->assertCount(4, Invoice::all());
        $filtered_invoices = Invoice::companyId($this->company->id)->orderBy('id')->get();
        $this->assertCount(2, $filtered_invoices);

        $this->assertEquals(
            $invoices_in_company->pluck('id'),
            $filtered_invoices->pluck('id')
        );
    }
}
