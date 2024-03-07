<?php

namespace App\Modules\Company\Services\Payments\Validator;

use Throwable;
use Exception;
use App\Models\Db\Module;

class ModuleValidatorFactory
{
    /**
     * Create validator.
     *
     * @param Module $module
     * @return mixed
     * @throws Exception
     */
    public function create(Module $module)
    {
        try {
            $name = studly_case(str_replace('.', '_', $module->slug)) . 'Validator';
            $name = '\\App\\Modules\\Company\\Services\\Payments\\Validator\\Module\\' . $name;

            return new $name();
        } catch (Throwable $e) {
            throw new Exception('Validator not exist.');
        }
    }
}
