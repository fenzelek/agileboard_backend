<?php

namespace App\Modules\Company\Services\Payments\Validator;

use Illuminate\Support\Collection;

class ModuleError
{
    private $errors;

    public function __construct()
    {
        $this->errors = new Collection();
    }

    public function add($error)
    {
        $this->errors->push($error);
    }

    public function hasErrors()
    {
        if (count($this->errors) > 0) {
            return true;
        }

        return false;
    }

    public function get()
    {
        return $this->errors;
    }
}
