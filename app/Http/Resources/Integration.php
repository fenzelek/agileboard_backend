<?php

namespace App\Http\Resources;

class Integration extends AbstractResource
{
    /**
     * @inheritdoc
     */
    protected $fields = [
        'id',
        'company_id',
        'integration_provider_id',
        'active',
        'created_at',
        'updated_at',
    ];

    /**
     * @inheritdoc
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);

        $settings = $this->settings;
        $data['public_settings'] = $settings ? $this->removeSecretKeys((array) $settings) : [];
        $data['created_at'] = $this->created_at ? $this->created_at->toDateTimeString() : null;
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;

        return $data;
    }
}
