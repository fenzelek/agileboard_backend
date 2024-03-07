<?php

namespace App\Models\Other\Integration\TimeTracking;

class Project
{
    /**
     * Id in external system.
     *
     * @var string
     */
    private $external_id;

    /**
     * Name in external system.
     *
     * @var string
     */
    private $external_name;

    /**
     * Project constructor.
     *
     * @param string $external_id
     * @param string $external_name
     */
    public function __construct($external_id, $external_name)
    {
        $this->external_id = $external_id;
        $this->external_name = $external_name;
    }

    /**
     * Get id in external system.
     *
     * @return string
     */
    public function getExternalId()
    {
        return $this->external_id;
    }

    /**
     * Get name in external system.
     *
     * @return string
     */
    public function getExternalName()
    {
        return $this->external_name;
    }
}
