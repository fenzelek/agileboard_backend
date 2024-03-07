<?php
declare(strict_types=1);

namespace App\Modules\CalendarAvailability\Models;

use App\Models\Db\User;
use App\Models\Db\UserAvailability;
use App\Models\Db\UserCompany;
use Illuminate\Support\Collection;

class UserWithoutAvailabilities
{
    private User $user;
    private UserCompany $user_company;

    public function __construct(User $user, UserCompany $user_company)
    {
        $this->user = $user;
        $this->user_company = $user_company;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return UserCompany
     */
    public function getUserCompany(): UserCompany
    {
        return $this->user_company;
    }
}