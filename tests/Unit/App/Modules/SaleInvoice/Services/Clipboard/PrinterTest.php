<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Clipboard;

use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceTypeStatus;
use App\Modules\SaleInvoice\Exceptions\NoAdditionalInvoiceDependenciesException;
use App\Modules\SaleInvoice\Exceptions\NoLoadInvoiceDependenciesException;
use Tests\Unit\App\Modules\SaleInvoice\Services\Clipboard\Helpers\CreateInvoice;
use App\Modules\SaleInvoice\Services\Clipboard\Printer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PrinterTest extends TestCase
{
    use CreateInvoice, DatabaseTransactions;

    protected $preserveGlobalState = false;
    protected $runTestInSeparateProcess = true;

    private $printer;
    private $invoice;

    protected function setUp():void
    {
        parent::setUp();

        $this->invoice = $this->getInvoice();
        $this->allDependencies($this->invoice);
        $this->printer = new Printer($this->invoice);
    }

    /** @test */
    public function render_return_pdf_stream()
    {
        $invoice_content = $this->printer->render();
        $this->assertTrue(is_string($invoice_content));
    }

    /** @test */
    public function render_printing_dependencies_exception()
    {
        $this->invoice->invoiceType = InvoiceType::findBySlug(InvoiceTypeStatus::CORRECTION);
        $this->expectException(NoAdditionalInvoiceDependenciesException::class);
        $this->invoice->corrected_invoice_id = 'correctedInvoice';
        $this->invoice->setRelation('correctedInvoice', null);
        $this->printer->render();
    }

    /** @test */
    public function render_no_load_required_dependencies_exception()
    {
        $this->expectException(NoLoadInvoiceDependenciesException::class);
        $this->invoice->setRelation('items', null);
        $this->printer->render();
    }

    /** @test */
    public function render_no_load_extracted_required_dependencies_exception()
    {
        $this->expectException(NoLoadInvoiceDependenciesException::class);
        $this->invoice->items[0]->setRelation('vatRate', null);
        $this->printer->render();
    }
}
