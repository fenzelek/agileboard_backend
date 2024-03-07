<?php

namespace App\Modules\Company\Services\Payments\Interfaces;

interface ErrorsStaff
{
    public function hasErrors(): bool;

    public function getErrors(): array;
}
