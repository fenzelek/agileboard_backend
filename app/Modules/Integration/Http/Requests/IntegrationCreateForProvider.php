<?php

namespace App\Modules\Integration\Http\Requests;

use App\Http\Requests\Request;

abstract class IntegrationCreateForProvider extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    abstract public function rules();

    /**
     * Get settings fields that should be saved.
     *
     * @return array
     */
    public function getSettingsFields()
    {
        return $this->only(array_keys($this->rules()))['settings'];
    }
}
