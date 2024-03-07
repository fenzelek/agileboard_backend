<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Db\Invoice as ModelInvoice;
use PDF;

class Invoice extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var ModelInvoice
     */
    public $invoice;

    /**
     * @var PDF
     */
    protected $pdf;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(ModelInvoice $invoice, $pdf)
    {
        $this->invoice = $invoice;
        $this->pdf = $pdf;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.invoice')
            ->subject("{$this->invoice->number} wystawiona przez {$this->invoice->company->name}")
            ->attachData($this->pdf->output(), 'faktura-' . str_slug($this->invoice->number) . '.pdf')
            ->with([
                'invoice' => $this->invoice,
                'email' => $this->to[0]['address'],
            ]);
    }
}
