<?php

declare(strict_types=1);

namespace App\Modules\CalendarAvailability\Services;

use App\Models\Db\User;
use App\Models\Db\UserCompany;
use App\Models\Other\SortDto;
use Illuminate\Support\Collection;

class CalendarAvailabilitySorter
{
    /**
     * @param Collection|User[] $users
     * @param SortDto[] $sorts
     * @return Collection|User[]
     */
    public function sort(Collection $users, array $sorts): Collection
    {
        if (count($sorts) === 0) {
            return $this->sortByLastName($users)->values();
        }

        /** @var SortDto $sort */
        foreach (array_reverse($sorts) as $sort) {
            switch ($sort->getField()) {
                case 'last_name':
                    $users = $this->sortByLastName($users, $sort->getDirection());
                    break;
                case 'department':
                    $users = $this->sortByDepartment($users, $sort->getDirection());
                    break;
                case 'contract_type':
                    $users = $this->sortByContractType($users, $sort->getDirection());
                    break;
            }
        }

        return $users->values();
    }

    /**
     * @param Collection|User[] $users
     * @return Collection|User[]
     */
    private function sortByLastName(Collection $users, string $direction='asc'): Collection
    {
        return $direction === 'desc' ?
            $users->sortByDesc('last_name') :
            $users->sortBy('last_name');
    }

    /**
     * @param Collection|User[] $users
     * @return Collection|User[]
     */
    private function sortByDepartment(Collection $users, string $direction='asc'): Collection
    {
        $callable = function (User $user) {
            /** @var UserCompany $user_company */
            $user_company = $user->userCompanies->first();

            return $user_company ? $user_company->department : '';
        };

        return $direction === 'desc' ?
            $users->sortByDesc($callable) :
            $users->sortBy($callable);
    }

    /**
     * @param Collection|User[] $users
     * @return Collection|User[]
     */
    private function sortByContractType(Collection $users, string $direction='asc'): Collection
    {
        $callable = function (User $user) {
            /** @var UserCompany $user_company */
            $user_company = $user->userCompanies->first();

            return $user_company ? $user_company->contract_type : '';
        };

        return $direction === 'desc' ?
            $users->sortByDesc($callable) :
            $users->sortBy($callable);
    }
}
