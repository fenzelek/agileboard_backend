<?php

namespace Tests\Unit\App\Modules\Company\Services\Payments\Validator\Module;

use App\Models\Db\ModuleMod;
use App\Modules\Company\Services\Payments\Validator\Module\GeneralWelcomeUrlValidator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GeneralWelcomeUrlValidatorTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function validate_success()
    {
        $mod = factory(ModuleMod::class)->create();
        $validator = new GeneralWelcomeUrlValidator();
        $this->assertTrue($validator->validate($mod));
    }

    /** @test */
    public function canUpdateCompanyModule_success()
    {
        $mod = factory(ModuleMod::class)->create();
        $validator = new GeneralWelcomeUrlValidator();
        $this->assertTrue($validator->canUpdateCompanyModule($mod));
    }
}
