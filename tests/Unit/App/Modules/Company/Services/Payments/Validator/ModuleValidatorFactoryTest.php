<?php

namespace Tests\Unit\App\Modules\Company\Services\Payments\Validator;

use App\Models\Db\Module;
use App\Modules\Company\Services\Payments\Validator\ModuleValidatorFactory;
use App\Modules\Company\Services\Payments\Validator\Module\InvoicesRegistryExportNameValidator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModuleValidatorFactoryTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function create_exception()
    {
        $module = factory(Module::class)->make(['slug' => 'test.module.not.exist']);

        $this->expectExceptionMessage('Validator not exist.');

        $factory = new ModuleValidatorFactory();
        $factory->create($module);
    }

    /** @test */
    public function create_success()
    {
        $module = factory(Module::class)->make(['slug' => 'invoices.registry.export.name']);

        $factory = new ModuleValidatorFactory();
        $validator = $factory->create($module, 'ExtendModule');
        $this->assertTrue($validator instanceof InvoicesRegistryExportNameValidator);
    }
}
