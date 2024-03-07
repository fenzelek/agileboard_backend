<?php

namespace App\Modules\Company\Contracts;

interface SubjectInterface
{
    public function isValid() : bool;

    public function getSubject();
}
