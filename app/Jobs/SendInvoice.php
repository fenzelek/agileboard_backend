<?php

namespace App\Jobs;

use App\Models\Db\EmailLog;
use App\Models\Other\ModuleType;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\Invoice as MailInvoice;
use PDF;
use DB;

class SendInvoice implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var EmailLog
     */
    public $email_log;

    /**
     * Create a new job instance.
     *
     * @param EmailLog $email_log
     * @return void
     */
    public function __construct(EmailLog $email_log)
    {
        $this->email_log = $email_log;
    }

    /**
     * Execute the job.
     * @return void
     */
    public function handle()
    {
        $pdf = PDF::loadView('pdf.invoice', [
            'invoice' => $this->email_log->invoice,
            'duplicate' => false,
            'bank_info' => $this->email_log->invoice->paymentMethod->paymentPostponed(),
            'footer' => $this->email_log->invoice->company
                ->appSettings(ModuleType::INVOICES_FOOTER_ENABLED),
        ]);
        $email_log = $this->email_log;

        DB::transaction(function () use ($email_log, $pdf) {
            Mail::to($email_log->email)->send(new MailInvoice($email_log->invoice, $pdf));

            $email_log->invoice->last_send_at = Carbon::now();
            $email_log->invoice->save();

            $email_log->update(['sent_at' => Carbon::now()]);
        });
    }
}
