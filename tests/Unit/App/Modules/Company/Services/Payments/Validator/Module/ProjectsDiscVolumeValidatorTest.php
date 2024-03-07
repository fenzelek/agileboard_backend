<?php

namespace Tests\Unit\App\Modules\Company\Services\Payments\Validator\Module;

use App\Models\Db\ModuleMod;
use App\Modules\Company\Services\Payments\Validator\Module\ProjectsDiscVolumeValidator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\App\Modules\Company\Services\Payments\Validator\Module\Traits\PackageModuleValidateTrait;

class ProjectsDiscVolumeValidatorTest extends TestCase
{
    use DatabaseTransactions, PackageModuleValidateTrait;

    public $validator;

    public function setUp():void
    {
        parent::setUp();
        $this->validator = new ProjectsDiscVolumeValidator();
    }

    /** @test */
    public function canUpdateCompanyModule_success()
    {
        $mod = factory(ModuleMod::class)->create();
        $this->assertTrue($this->validator->canUpdateCompanyModule($mod));
    }
}
