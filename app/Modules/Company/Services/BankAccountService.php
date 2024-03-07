<?php

namespace App\Modules\Company\Services;

use App\Models\Db\BankAccount;
use App\Models\Db\Company as CompanyModel;
use Illuminate\Support\Collection;

class BankAccountService
{
    /**
     * @var BankAccount
     */
    private $bank_account;

    /**
     * CompanyModel constructor.
     *
     * @param BankAccount $bank_account
     */
    public function __construct(BankAccount $bank_account)
    {
        $this->bank_account = $bank_account;
    }

    public function sync(CompanyModel $company, array $raw_bank_accounts)
    {
        $this->removeOld($company, $raw_bank_accounts);
        $this->updateExisting($company, $raw_bank_accounts);
        $this->createNews($company, $raw_bank_accounts);
    }

    /**
     * @param array $raw_bank_accounts
     * @return Collection
     */
    protected function getUpdatedBankAccounts(array $raw_bank_accounts): Collection
    {
        return collect($raw_bank_accounts)->where('id', '!==', null);
    }

    /**
     * @param array $raw_bank_accounts
     * @return Collection
     */
    protected function getNewBankAccounts(array $raw_bank_accounts): Collection
    {
        return collect($raw_bank_accounts)->where('id', 'IS', null);
    }

    /**
     * @param CompanyModel $company
     * @param array $raw_bank_accounts
     */
    protected function removeOld(CompanyModel $company, array $raw_bank_accounts)
    {
        $company->bankAccounts()
            ->whereNotIn('id', $this->getUpdatedBankAccounts($raw_bank_accounts)->pluck('id')->all())
            ->delete();
    }

    /**
     * @param CompanyModel $company
     * @param array $raw_bank_accounts
     */
    protected function updateExisting(CompanyModel $company, array $raw_bank_accounts)
    {
        $this->getUpdatedBankAccounts($raw_bank_accounts)
            ->each(function ($bank_account) use ($company) {
                $company->bankAccounts()
                    ->where('id', array_get($bank_account, 'id'))
                    ->update(collect($bank_account)->only([
                        'number',
                        'bank_name',
                        'default',
                    ])->all());
            });
    }

    /**
     * @param CompanyModel $company
     * @param array $raw_bank_accounts
     */
    protected function createNews(CompanyModel $company, array $raw_bank_accounts)
    {
        $this->getNewBankAccounts($raw_bank_accounts)
            ->each(function ($bank_account) use ($company) {
                $company->bankAccounts()->create(collect($bank_account)->only([
                    'number',
                    'bank_name',
                    'default',
                ])->all());
            });
    }
}
