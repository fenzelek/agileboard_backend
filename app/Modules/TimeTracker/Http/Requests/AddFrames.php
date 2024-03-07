<?php

namespace App\Modules\TimeTracker\Http\Requests;

use App\Http\Requests\Request;
use App\Modules\TimeTracker\DTO\AddFrame;
use App\Modules\TimeTracker\DTO\Contracts\IAddFrame;
use App\Modules\TimeTracker\Http\Requests\Contracts\IAddFrames;

class AddFrames extends Request implements IAddFrames
{
    public function rules(): array
    {
        return [
            'frames' => ['required', 'array'],
            'frames.*.from' => ['required', 'integer', 'gt:0'],
            'frames.*.to' => ['required', 'integer', 'gte:frames.*.from'],
            'frames.*.companyId' => ['required', 'integer', 'gt:0'],
            'frames.*.projectId' => ['required', 'integer', 'gt:0'],
            'frames.*.taskId' => ['required', 'integer', 'gt:0'],
            'frames.*.screens' => ['array'],
            'frames.*.screens.*' => ['string'],
            'frames.*.activity' => ['required', 'integer', 'gte:0'],
            'frames.*.gpsPosition' => ['nullable', 'array'],
            'frames.*.gpsPosition.latitude' => ['numeric'],
            'frames.*.gpsPosition.longitude' => ['numeric'],
        ];
    }

    /**
     * @return IAddFrame[]
     */
    public function getFrames(): iterable
    {
        foreach ($this->input('frames') as $frame) {
            yield new AddFrame($frame);
        }
    }
}
