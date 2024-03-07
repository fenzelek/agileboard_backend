<?php

declare(strict_types=1);

namespace App\Modules\Agile\Contracts;

use App\Models\Db\User;

interface IWidget
{
    public function get(User $user);
    public function getName(): string;
}
