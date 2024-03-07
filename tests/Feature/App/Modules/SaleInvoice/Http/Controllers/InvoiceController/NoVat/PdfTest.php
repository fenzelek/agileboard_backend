<?php

namespace Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController\NoVat;

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceRegistry;
use App\Models\Db\InvoiceReverseCharge;
use App\Models\Db\InvoiceType;
use App\Models\Db\Package;
use App\Models\Db\VatRate;
use App\Models\Other\InvoiceCorrectionType;
use App\Models\Other\InvoiceReverseChargeType;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\SaleInvoice\Payers\NoVat;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\App\Modules\SaleInvoice\Http\Controllers\InvoiceController\Preload\FinancialEnvironment;
use Carbon\Carbon;
use Tests\Feature\App\Modules\SaleInvoice\Helpers\FinancialEnvironment as FinancialEnvironmentTrait;
use File;

class PdfTest extends FinancialEnvironment
{
    use DatabaseTransactions, FinancialEnvironmentTrait;

    private $now;
    private $registry;
    private $company;
    private $no_vat_rate;
    private $pdf_content;
    private $invoice;
    private $array;
    private $text_file;
    private $file;

    public function setUp():void
    {
        parent::setUp();
        $this->registry = factory(InvoiceRegistry::class)->create();
        $this->now = Carbon::now();
        Carbon::setTestNow($this->now);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_print_no_vat_payer_invoice()
    {
        $this->noVatPrintingEnvironment();

        $this->array = [
            'Sprzedawca',
            'Faktura',
            'nr sample_number',
            'Lp',
            'Cena jednostkowa',
            'Wartość',
            number_format_output(200),
            number_format_output(2000),
            'Razem',
            number_format_output(5600),
            'Faktura nr sample_number',
        ];

        ob_start();

        $this->get('invoices/' . $this->invoice->id . '/pdf?selected_company_id=' . $this->company->id)
            ->assertResponseOk();

        $this->pdf_content = ob_get_clean();

        $this->assertSamePdf();
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_print_no_vat_payer_advance_invoice()
    {
        $this->noVatPrintingEnvironment();

        $this->invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id,
            'proforma_id' => $this->createInvoice(InvoiceTypeStatus::PROFORMA)->id,
        ]);
        $this->array = [
            'Sprzedawca',
            'Faktura Zaliczkowa',
            'nr sample_number',
            'Do proformy:',
            $this->invoice->proforma->number,
            'Data wystawienia:',
            $this->invoice->issue_date,
            'Data otrzymania zaliczki',
            $this->invoice->sale_date,
            'Lp',
            'Cena jednostkowa',
            'Wartość',
            number_format_output(200),
            number_format_output(2000),
            'Razem',
            number_format_output(5600),
            'Faktura Zaliczkowa nr sample_number',
        ];

        ob_start();

        $this->get('invoices/' . $this->invoice->id . '/pdf?selected_company_id=' . $this->company->id)
            ->assertResponseOk();

        $this->pdf_content = ob_get_clean();

        $this->assertSamePdf();
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_print_no_vat_payer_reverse_charge_invoice()
    {
        $this->noVatPrintingEnvironment();

        $this->invoice->update([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::REVERSE_CHARGE)->id,
            'invoice_reverse_charge_id' => InvoiceReverseCharge::findBySlug(InvoiceReverseChargeType::IN)->id,
        ]);
        $this->array = [
            'Sprzedawca',
            'Faktura',
            'nr sample_number',
            'Data wystawienia:',
            $this->invoice->issue_date,
            'Lp',
            'Cena jednostkowa',
            'Wartość',
            number_format_output(200),
            number_format_output(2000),
            'Razem',
            number_format_output(5600),
            'Faktura nr sample_number',
        ];

        ob_start();

        $this->get('invoices/' . $this->invoice->id . '/pdf?selected_company_id=' . $this->company->id)
            ->assertResponseOk();

        $this->pdf_content = ob_get_clean();

        $this->assertSamePdf();
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function pdf_print_no_vat_payer_correction_invoice()
    {
        list($this->directory, $this->file, $this->text_file, $this->company, $this->invoice) = $this->setCorrectionInvoicePrintingEnvironment(Package::START);

        $invoice_corrected = factory(Invoice::class)->create([
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::REVERSE_CHARGE)->id,
            'invoice_reverse_charge_id' => InvoiceReverseCharge::findBySlug(InvoiceReverseChargeType::IN)->id,
        ]);

        $this->invoice->update([
            'correction_type' => InvoiceCorrectionType::QUANTITY,
            'corrected_invoice_id' => $invoice_corrected->id,
            'invoice_type_id' => InvoiceType::findBySlug(InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION)->id,
            'invoice_reverse_charge_id' => InvoiceReverseCharge::findBySlug(InvoiceReverseChargeType::IN)->id,
            'gross_counted' => NoVat::COUNT_TYPE,
        ]);

        $this->company->update(['vat_payer' => false]);

        $this->array = [
            'Sprzedawca',
            'Faktura',
            'nr sample_number',
            'Data wystawienia:',
            $this->invoice->issue_date,
            'Lp',
            'Cena jednostkowa',
            'Wartość',
            'Lp',
            'Cena jednostkowa',
            'Wartość',
            number_format_output(200),
            number_format_output(2000),
            'Razem',
            number_format_output(4300),
            'Faktura Korekta nr sample_number',
        ];

        ob_start();

        $this->get('invoices/' . $this->invoice->id . '/pdf?selected_company_id=' . $this->company->id)
            ->assertResponseOk();

        $this->pdf_content = ob_get_clean();

        $this->assertSamePdf();
    }

    protected function noVatPrintingEnvironment()
    {
        list($this->directory, $this->file, $this->text_file, $this->company, $this->invoice) =
            $this->setInvoicePrintingEnvironment(Package::START);
        $this->no_vat_rate = VatRate::findByName(NoVat::VAT_RATE);
        $this->company->update(['vat_payer' => false]);

        $this->invoice->items()->update([
            'vat_rate_id' => $this->no_vat_rate->id,
        ]);

        $this->invoice->taxes()->update([
            'vat_rate_id' => $this->no_vat_rate->id,
        ]);

        $this->invoice->update([
            'gross_counted' => NoVat::COUNT_TYPE,
        ]);
    }

    private function assertSamePdf()
    {
        if (config('test_settings.enable_test_pdf')) {
            // save PDF file content
            File::put($this->file, $this->pdf_content);
            exec('pdftotext -layout "' . $this->file . '" "' . $this->text_file . '"');
            $text_content = file_get_contents($this->text_file);
            $this->assertContainsOrdered($this->array, $text_content);
            File::deleteDirectory($this->directory);
        }
    }
}
