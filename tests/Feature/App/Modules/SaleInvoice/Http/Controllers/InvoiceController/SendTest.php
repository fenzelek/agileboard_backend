<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController;

use App\Jobs\SendInvoice;
use App\Models\Db\Company;
use App\Models\Db\EmailLog;
use App\Models\Db\PaymentMethod;
use App\Models\Other\PaymentMethodType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController\Preload\FinancialEnvironment;
use App\Mail\Invoice as MailInvoice;

class SendTest extends FinancialEnvironment
{
    use DatabaseTransactions;

    /** @test */
    public function send_user_has_permission()
    {
        list($company, $invoice, $invoice_node, $receipt, $invoice_items, $online_sale, $invoice_taxes, $invoice_company, $invoice_contractor, $invoice_delivery_address) = $this->setInvoiceForAttributes();

        $this->post('/invoices/' . $invoice->id . '/send?selected_company_id=' . $company->id)
            ->seeStatusCode(422);
    }

    /** @test */
    public function send_validation_email_error()
    {
        list($company, $invoice) = $this->setInvoiceForAttributes();

        $this->post('/invoices/' . $invoice->id . '/send?selected_company_id=' . $company->id . '&email=no_valid_email')
            ->seeStatusCode(422);

        $this->verifyValidationResponse(['email']);
    }

    /** @test */
    public function send_validation_invoice_id_error()
    {
        list($company, $invoice) = $this->setInvoiceForAttributes();
        $other_company = factory(Company::class)->create();
        $invoice->update([
            'company_id' => $other_company->id,
        ]);
        $this->post('/invoices/' . $invoice->id . '/send?selected_company_id=' . $company->id)
            ->seeStatusCode(422);

        $this->verifyValidationResponse(['id']);
    }

    /** @test */
    public function send_add_job_to_queue_to_queue()
    {
        list($company, $invoice) = $this->setInvoiceForAttributes();

        $this->expectsJobs(SendInvoice::class);

        $this->post('/invoices/' . $invoice->id . '/send?selected_company_id=' . $company->id, ['email' => 'contractor@email.com'])
            ->seeStatusCode(200);
    }

    /** @test */
    public function send_add_log_to_database()
    {
        Queue::fake();
        list($company, $invoice) = $this->setInvoiceForAttributes();

        $this->post('/invoices/' . $invoice->id . '/send?selected_company_id=' . $company->id, ['email' => 'contractor@email.com'])
            ->seeStatusCode(200);

        $this->assertSame(1, EmailLog::count());

        $email_log = EmailLog::latest('id')->first();

        $invoice = $invoice->fresh();

        $this->assertSame('contractor@email.com', $email_log->email);
        $this->assertSame('Invoice', $email_log->title);
        $this->assertSame($this->user->id, $email_log->user_id);
        $this->assertSame($invoice->id, $email_log->invoice_id);
        $this->assertNull($email_log->sent_at);
        $this->assertNotNull('created_at');
        $this->assertNotNull('updated_at');
    }

    /** @test */
    public function send_queue_service()
    {
        Queue::fake();
        $now = Carbon::parse('2017-01-01');
        Carbon::setTestNow($now);

        list($company, $invoice) = $this->setInvoiceForAttributes();

        $this->post('/invoices/' . $invoice->id . '/send?selected_company_id=' . $company->id, ['email' => 'contractor@email.com'])
            ->seeStatusCode(200);

        $email_log = EmailLog::latest('id')->first();
        Queue::assertPushed(SendInvoice::class, function ($job) use ($email_log, $invoice) {
            return $job->email_log->id == $email_log->id;
        });
        $email_log = $email_log->fresh();
        $this->assertSame('contractor@email.com', $email_log->email);
        $this->assertSame('Invoice', $email_log->title);
        $this->assertSame($this->user->id, $email_log->user_id);
        $this->assertSame($invoice->id, $email_log->invoice_id);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function send_mail_was_sent_and_updated_log()
    {
        Mail::fake();

        $now = Carbon::parse('2017-01-01');
        Carbon::setTestNow($now);

        list($company, $invoice) = $this->setInvoiceForAttributes();

        $this->post('/invoices/' . $invoice->id . '/send?selected_company_id=' . $company->id, ['email' => 'contractor@email.com'])
            ->seeStatusCode(200);

        $email_log = EmailLog::latest('id')->first();
        $dispatcher = App::make(SendInvoice::class);
        $dispatcher->email_log = $email_log;
        $dispatcher->handle();

        Mail::assertSent(MailInvoice::class, function ($mailable) {
            return $mailable->hasTo('contractor@email.com');
        });

        $email_log = $email_log->fresh();
        $invoice = $invoice->fresh();
        $this->assertEquals(Carbon::now()->toDateTimeString(), $email_log->sent_at);
        $this->assertEquals(Carbon::now()->toDateTimeString(), $invoice->last_send_at);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function send_correct_mail_content_for_postponed_payment_method()
    {
        Mail::fake();

        $now = Carbon::parse('2017-01-01');
        Carbon::setTestNow($now);

        list($company, $invoice) = $this->setInvoiceForAttributes();

        $invoice->invoiceCompany->update([
            'name' => 'Test Company',
            'main_address_street' => 'Testowa',
            'main_address_number' => '1/12',
            'main_address_zip_code' => '11-111',
            'main_address_city' => 'Test City',
            'vatin' => '1234567890',
            'bank_name' => 'Test Bank',
            'bank_account_number' => '123456789123456789',
        ]);

        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::BANK_TRANSFER);
        $invoice->paymentMethod()->associate($payment_method);
        $invoice->save();

        $this->post('/invoices/' . $invoice->id . '/send?selected_company_id=' . $company->id, ['email' => 'contractor@email.com'])
            ->seeStatusCode(200);

        $email_log = EmailLog::latest('id')->first();
        $dispatcher = App::make(SendInvoice::class);
        $dispatcher->email_log = $email_log;
        $dispatcher->handle();

        Mail::assertSent(MailInvoice::class, function ($mail) use ($invoice) {
            $mail->build();

            return $mail->subject == $invoice->number . ' wystawiona przez ' . $invoice->company->name;
        });
        Mail::assertSent(MailInvoice::class, function ($mail) use ($invoice) {
            $mail->build();

            return $mail->subject == $invoice->number . ' wystawiona przez ' . $invoice->company->name;
        });

        Mail::assertSent(MailInvoice::class, function ($mail) use ($invoice) {
            $assertion_needles = [
                '123',
                'z dnia 01.08.2017',
                '64.18 PLN',
                'termin płatności upływa 11.08.2017',
                'Bank: Test Bank',
                'Numer konta: 123456789123456789',
                'Test Company',
                'Testowa',
                '1/12',
                '11-111',
                'Test City',
                '1234567890',
            ];
            $assertion_not_contain_needles = [
                'opłacono gotówką',
            ];
            $mail->build();
            $render = view($mail->view, $mail->viewData)->render();

            return $this->strContainAllNeedles($render, $assertion_needles) && $this->strNotContainAllNeedles($render, $assertion_not_contain_needles);
        });
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function send_correct_mail_content_for_payment_in_advance()
    {
        Mail::fake();

        $now = Carbon::parse('2017-01-01');
        Carbon::setTestNow($now);

        list($company, $invoice) = $this->setInvoiceForAttributes();

        $payment_method = PaymentMethod::findBySlug(PaymentMethodType::CASH);
        $invoice->paymentMethod()->associate($payment_method);
        $invoice->save();

        $this->post('/invoices/' . $invoice->id . '/send?selected_company_id=' . $company->id, ['email' => 'contractor@email.com'])
            ->seeStatusCode(200);

        $email_log = EmailLog::latest('id')->first();
        $dispatcher = App::make(SendInvoice::class);
        $dispatcher->email_log = $email_log;
        $dispatcher->handle();

        Mail::assertSent(MailInvoice::class, function ($mail) use ($invoice) {
            $assertion_needles = [
                '123',
                'z dnia 01.08.2017',
                '64.18 PLN',
                'opłacono gotówką',
            ];
            $assertion_not_contain_needles = [
                'Bank',
                'Numer konta',
            ];
            $mail->build();
            $render = view($mail->view, $mail->viewData)->render();

            return $this->strContainAllNeedles($render, $assertion_needles) && $this->strNotContainAllNeedles($render, $assertion_not_contain_needles);
        });
    }

    public function strContainAllNeedles($render, $needles)
    {
        return collect($needles)->filter(function ($needle) use ($render) {
            return ! str_contains($render, $needle);
        })->count() == 0;
    }

    public function strNotContainAllNeedles($render, $needles)
    {
        return collect($needles)->filter(function ($needle) use ($render) {
            return str_contains($render, $needle);
        })->count() == 0;
    }
}
