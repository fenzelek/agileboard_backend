<?php

namespace App\Modules\TimeTracker\Http\Requests\Contracts;

use App\Modules\TimeTracker\DTO\Contracts\IAddFrame;

interface IAddFrames
{
    /**
     * @return IAddFrame[]
     */
    public function getFrames(): iterable;
}
