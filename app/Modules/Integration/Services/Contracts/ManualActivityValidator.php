<?php

namespace App\Modules\Integration\Services\Contracts;

interface ManualActivityValidator
{
    public function validate(RemoveActivityProvider $activity_data_provider):bool;
}
