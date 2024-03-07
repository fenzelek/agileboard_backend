<?php

namespace App\Models\Other;

class UserCompanyStatus
{
    /**
     * User has approved invitation to company so he is assigned to this company.
     */
    const APPROVED = 1;

    /**
     * User has refused invitation to company.
     */
    const REFUSED = 2;

    /**
     * User has been suspended (@todo - how it should work ?).
     */
    const SUSPENDED = 3;

    /**
     * User has been deleted from company - he left this company or company
     * removed the user (so he is not assigned and cannot
     * run any actions for data related to this company).
     */
    const DELETED = 4;

    /**
     * Get all available statuses for user.
     *
     * @return array
     */
    public static function all()
    {
        return [
            self::APPROVED,
            self::REFUSED,
            self::SUSPENDED,
            self::DELETED,
        ];
    }
}
