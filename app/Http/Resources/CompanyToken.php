<?php

namespace App\Http\Resources;

use App\Modules\Company\Services\Token;

class CompanyToken extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = '*';

    /**
     * @inheritdoc
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['api_token'] = app(Token::class)->encode($this->resource->toApiToken());
        unset($data['token']);

        return $data;
    }
}
