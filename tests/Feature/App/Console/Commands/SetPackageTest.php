<?php

namespace Tests\Feature\App\Console\Commands;

use App\Models\Db\Package;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SetPackageTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function handle_wrongPackage()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::CEP_FREE);

        $package_id = $company->realPackage()->id;

        $data = [
            'package' => 'test',
            'company' => $company->id,
        ];

        $this->artisan('set-package', $data);

        $this->assertSame($package_id, $company->fresh()->realPackage()->id);
    }

    /** @test */
    public function handle_success_changeToEnterprise()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::CEP_FREE);

        $data = [
            'package' => 'enterprise',
            'company' => $company->id,
        ];

        $this->artisan('set-package', $data);

        $this->assertSame(Package::findBySlug(Package::CEP_ENTERPRISE)->id, $company->fresh()->realPackage()->id);
    }

    /** @test */
    public function handle_success_changeToDefault()
    {
        $this->createUser();
        $company = $this->createCompanyWithRoleAndPackage(RoleType::OWNER, Package::CEP_ENTERPRISE);

        $data = [
            'package' => 'default',
            'company' => $company->id,
        ];

        $this->artisan('set-package', $data);

        $this->assertSame(Package::findDefault()->id, $company->fresh()->realPackage()->id);
    }
}
