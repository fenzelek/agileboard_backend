<?php

namespace Tests\Unit\App\Modules\Company\Services\Payments\Validator\Module;

use App\Models\Db\ModuleMod;
use App\Modules\Company\Services\Payments\Validator\Module\InvoicesActiveValidator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvoicesActiveValidatorTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function validate_success()
    {
        $mod = factory(ModuleMod::class)->create();
        $validator = new InvoicesActiveValidator();
        $this->assertTrue($validator->validate($mod));
    }

    /** @test */
    public function canUpdateCompanyModule_success()
    {
        $mod = factory(ModuleMod::class)->create();
        $validator = new InvoicesActiveValidator();
        $this->assertTrue($validator->canUpdateCompanyModule($mod));
    }
}
