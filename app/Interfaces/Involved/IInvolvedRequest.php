<?php

declare(strict_types=1);

namespace App\Interfaces\Involved;

interface IInvolvedRequest
{
    public function getSelectedCompanyId(): int;

    public function getProjectId(): int;

    /** @return int[] */
    public function getInvolvedIds(): array;
}
