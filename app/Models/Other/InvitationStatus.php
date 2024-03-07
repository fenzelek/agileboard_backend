<?php

namespace App\Models\Other;

class InvitationStatus
{
    /**
     * Invitation was created.
     */
    const PENDING = 0;

    /**
     * User has approved invitation to company so he is assigned to this company.
     */
    const APPROVED = 1;

    /**
     * User has rejected invitation to company.
     */
    const REJECTED = 2;

    /**
     * Invitation was deleted.
     */
    const DELETED = 4;
}
