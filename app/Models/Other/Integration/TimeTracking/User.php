<?php

namespace App\Models\Other\Integration\TimeTracking;

class User
{
    /**
     * Id in external system.
     *
     * @var string
     */
    private $external_id;

    /**
     * E-mail in external system.
     *
     * @var string
     */
    private $external_email;

    /**
     * Name in external system.
     *
     * @var string
     */
    private $external_name;

    /**
     * User constructor.
     *
     * @param string $external_id
     * @param string $external_name
     * @param $external_email
     */
    public function __construct($external_id, $external_email, $external_name)
    {
        $this->external_id = $external_id;
        $this->external_name = $external_name;
        $this->external_email = $external_email;
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
     * Get e-mail in external system.
     *
     * @return string
     */
    public function getExternalEmail()
    {
        return $this->external_email;
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
