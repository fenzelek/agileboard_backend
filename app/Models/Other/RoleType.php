<?php

namespace App\Models\Other;

class RoleType
{
    /**
     * Company owner.
     */
    const OWNER = 'owner';

    /**
     * Company admin.
     */
    const ADMIN = 'admin';

    /**
     * Company dealer.
     */
    const DEALER = 'dealer';

    /**
     * Company developer.
     */
    const DEVELOPER = 'developer';

    /**
     * Company client.
     */
    const CLIENT = 'client';

    /**
     * System admin.
     */
    const SYSTEM_ADMIN = 'system_admin';

    /**
     * System user.
     */
    const SYSTEM_USER = 'system_user';

    /**
     * Company employee.
     */
    const EMPLOYEE = 'employee';

    /**
     * Tax office.
     */
    const TAX_OFFICE = 'tax_office';

    /**
     * Role for API user.
     */
    const API_USER = 'api.user';

    /**
     * Role for API company.
     */
    const API_COMPANY = 'api.company';

    /**
     * Get all available company role types.
     *
     * @return array
     */
    public static function all()
    {
        return [
            self::OWNER,
            self::ADMIN,
            self::DEALER,
            self::DEVELOPER,
            self::CLIENT,
            self::EMPLOYEE,
            self::TAX_OFFICE,
        ];
    }

    /**
     * Get default user role.
     *
     * @return string
     */
    public static function default()
    {
        return self::CLIENT;
    }
}
