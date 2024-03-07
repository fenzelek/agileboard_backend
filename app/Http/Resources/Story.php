<?php

namespace App\Http\Resources;

class Story extends AbstractResource
{
    // this transformer is required for StoryController@store - probably bug in ApiResponse
    // if we use multiple transformers and don't use this, those 2 won't be put in data key as they
    // should be
    protected $fields = '*';

    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['created_at'] = $this->created_at ? $this->created_at->toDateTimeString() : null;
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;

        return $data;
    }
}
