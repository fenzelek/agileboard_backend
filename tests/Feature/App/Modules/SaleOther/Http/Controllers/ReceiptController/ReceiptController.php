<?php

namespace Tests\Feature\App\Modules\SaleOther\Http\Controllers\ReceiptController;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Db\Company;
use Tests\BrowserKitTestCase;

abstract class ReceiptController extends BrowserKitTestCase
{
    use DatabaseTransactions;

    protected function login_user_and_return_company_with_his_employee_role()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::OWNER);
        $now = Carbon::now();
        Carbon::setTestNow($now);

        return $company;
    }

    protected function assignReceiptsToCompany(Collection $receipts, Company $company)
    {
        $receipts->each(function ($receipt) use ($company) {
            $receipt->company_id = $company->id;
            $receipt->save();
        });
    }
}
