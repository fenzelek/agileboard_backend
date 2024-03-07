<?php

namespace Tests\Feature\App\Jobs;

use App\Jobs\SendInvoice;
use App\Models\Db\EmailLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController\Preload\FinancialEnvironment;

class SendInvoiceTest extends FinancialEnvironment
{
    use DatabaseTransactions;

    protected function setUp():void
    {
        parent::setUp();
    }

    /** @test */
    public function testit_doesnt_throw_exception_for_2_invoices_with_different_numbers_for_same_company()
    {
        Mail::fake();
        list($company, $invoice) = $this->setInvoiceForAttributes();
        auth()->logout();
        $email_log = new EmailLog();
        $email_log->invoice = $invoice;
        $email_log->email = 'test@company.com';
        $send_invoice = new SendInvoice($email_log);
        $failed = false;

        try {
            $send_invoice->handle();
        } catch (\Exception $exception) {
            $failed = true;
        }

        $this->assertFalse($failed);
    }
}
