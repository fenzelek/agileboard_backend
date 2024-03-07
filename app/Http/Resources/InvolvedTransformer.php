<?php

namespace App\Http\Resources;

use App\Models\Db\Involved;

class InvolvedTransformer extends AbstractResource
{
    /**
     * @property Involved $resource
     */

    public function toArray($request)
    {
        $data['user_id'] = $this->resource->user_id;
        $data['first_name'] = $this->resource->user->first_name;
        $data['last_name'] = $this->resource->user->last_name;
        $data['avatar'] = $this->resource->user->avatar;

        return $data;
    }
}
