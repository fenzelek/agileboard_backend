<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\InvoiceFiller;

use App\Models\Db\Invoice;
use App\Models\Other\SaleReport\Optima\TaxItem;
use App\Modules\SaleReport\Services\Optima\FieldFiller;
use App\Modules\SaleReport\Services\Optima\GenericFieldFiller;
use App\Modules\SaleReport\Services\Optima\InvoiceFiller;
use App\Modules\SaleReport\Services\Optima\TaxesFiller;
use Tests\TestCase;
use Mockery;

class GetFieldsTest extends TestCase
{
    protected $invoice_number = 'ABC/324/21';
    protected $corrected_invoice_number = 'ORG/544/123';
    protected $document_type = 'TYP_DOKUMENTU';
    protected $receipt_status = 'STATUS_PAR';
    protected $contractor_name_1 = 'Con 1 name';
    protected $contractor_name_2 = 'Con 2 name';
    protected $contractor_address = 'Con address';
    protected $contractor_zipcode = 'Contractor zip';
    protected $contractor_city = 'City of contractor';
    protected $contractor_vatin = 'SAMPLE_CONTRACOTR_VATIN';
    protected $contractor_type = 'PODMIOT_GOSP_CONTRACTOR';
    protected $export_type = 'C_EXPORT';
    protected $paid_status = 'PAID_BY_CONTRACTOR';
    protected $payment_method = 'BUSINESS PAYMENT';
    protected $deduction_value = 'SAMPLE_DEDUCTION_VALUE';

    /** @test */
    public function it_returns_valid_row_for_given_invoice()
    {
        /** @var Invoice $invoice */
        $invoice = Mockery::mock(Invoice::class)->makePartial();
        $invoice->forceFill([
            'id' => 17,
            'issue_date' => '2017-11-30',
            'sale_date' => '2017-08-20',
            'payment_term_days' => 16,
            'price_gross' => '-450023',
        ]);
        $invoice->id = 17;
        $invoice->issue_date = '2017-11-30';
        $invoice->sale_date = '2017-08-20';
        $invoice->shouldReceive('getRawPaidAmount')->once()->withNoArgs()->andReturn(331517);

        $field_filler = $this->createAndSetFieldFillerExpectations($invoice);
        $tax_filler = $this->createAndSetTaxFillerExpectations($invoice);

        $generic_filler = new GenericFieldFiller();

        $filler = new InvoiceFiller($generic_filler, $field_filler, $tax_filler);

        $fields = $filler->getFields($invoice);
        $expected_fields = $this->getExpectedFields($invoice, $generic_filler);

        $this->assertSame(array_keys($expected_fields), array_keys($fields));

        foreach ($expected_fields as $key => $val) {
            $this->assertSame(
                $expected_fields[$key],
                $fields[$key],
                'Field ' . $key . ' has correct value'
            );
        }
    }

    protected function getExpectedFields(Invoice $invoice, GenericFieldFiller $generic_filler)
    {
        $fields = $generic_filler->fieldsWithDefaultValues();

        return array_merge($fields, [
            'ID' => $invoice->id,
            'GRUPA' => 'SPRZE',
            'DATA_TR' => '17/11/30',
            'DATA_WYST' => '17/08/20',
            'DOKUMENT' => $this->invoice_number,
            'KOREKTA_DO' => $this->corrected_invoice_number,
            'TYP' => 2,
            'KOREKTA' => $this->document_type,
            'ZAKUP' => 0,
            'ODLICZENIA' => $this->deduction_value,
            'KASA' => $this->receipt_status,
            'K_NAZWA1' => $this->contractor_name_1,
            'K_NAZWA2' => $this->contractor_name_2,
            'K_ADRES1' => $this->contractor_address,
            'K_KODP' => $this->contractor_zipcode,
            'K_MIASTO' => $this->contractor_city,
            'NIP' => $this->contractor_vatin,
            'FIN' => $this->contractor_type,
            'EXPORT' => $this->export_type,
            'ROZLICZONO' => $this->paid_status,
            'PLATNOSC' => $this->payment_method,
            'TERMIN' => '17/12/16',
            'BRUTTO' => '-4500.23',
            'ZAPLATA' => '3315.17',
        ], $this->getExpectedTaxesFields());
    }

    protected function getTaxItems()
    {
        return [
            new TaxItem(23, 150123, 1451, 'TYPE_FOR_1'),
            new TaxItem(7, 1421312, 132551, 'TYPE_FOR_2'),
            new TaxItem(0, -150123, -1451, 'TYPE_FOR_3'),
            new TaxItem(0, -1421312, -132551, 'TYPE_FOR_4'),
        ];
    }

    protected function getExpectedTaxesFields()
    {
        return [
            'FLAGA_1' => 'TYPE_FOR_1',
            'STAWKA_1' => '23.00',
            'NETTO_1' => '1501.23',
            'VAT_1' => '14.51',
            'FLAGA_2' => 'TYPE_FOR_2',
            'STAWKA_2' => '7.00',
            'NETTO_2' => '14213.12',
            'VAT_2' => '1325.51',
            'FLAGA_3' => 'TYPE_FOR_3',
            'STAWKA_3' => '0.00',
            'NETTO_3' => '-1501.23',
            'VAT_3' => '-14.51',
            'FLAGA_4' => 'TYPE_FOR_4',
            'STAWKA_4' => '0.00',
            'NETTO_4' => '-14213.12',
            'VAT_4' => '-1325.51',
        ];
    }

    protected function createAndSetFieldFillerExpectations(Invoice $invoice)
    {
        /** @var FieldFiller $field_filler */
        $field_filler = Mockery::mock(FieldFiller::class);
        $field_filler->shouldReceive('setInvoice')->once()
            ->with(Mockery::on(function ($arg) use ($invoice) {
                return $arg instanceof Invoice && $arg->id == $invoice->id;
            }));
        $field_filler->shouldReceive('getDocumentNumber')->once()->withNoArgs()
            ->andReturn($this->invoice_number);
        $field_filler->shouldReceive('getCorrectedDocumentNumber')->once()->withNoArgs()
            ->andReturn($this->corrected_invoice_number);
        $field_filler->shouldReceive('getDocumentType')->once()->withNoArgs()
            ->andReturn($this->document_type);
        $field_filler->shouldReceive('getReceiptStatus')->once()->withNoArgs()
            ->andReturn($this->receipt_status);
        $field_filler->shouldReceive('getContractorName')->once()->withNoArgs()
            ->andReturn([$this->contractor_name_1, $this->contractor_name_2]);
        $field_filler->shouldReceive('getContractorAddress')->once()->withNoArgs()
            ->andReturn($this->contractor_address);
        $field_filler->shouldReceive('getContractorZipCode')->once()->withNoArgs()
            ->andReturn($this->contractor_zipcode);
        $field_filler->shouldReceive('getContractorCity')->once()->withNoArgs()
            ->andReturn($this->contractor_city);
        $field_filler->shouldReceive('getContractorVatin')->once()->withNoArgs()
            ->andReturn($this->contractor_vatin);
        $field_filler->shouldReceive('getContractorType')->once()->withNoArgs()
            ->andReturn($this->contractor_type);
        $field_filler->shouldReceive('getExportType')->once()->withNoArgs()
            ->andReturn($this->export_type);
        $field_filler->shouldReceive('getPaidStatus')->once()->withNoArgs()
            ->andReturn($this->paid_status);
        $field_filler->shouldReceive('getPaymentMethod')->once()->withNoArgs()
            ->andReturn($this->payment_method);
        $field_filler->shouldReceive('getDeductionValue')->once()->with($this->export_type)
            ->andReturn($this->deduction_value);

        return $field_filler;
    }

    protected function createAndSetTaxFillerExpectations(Invoice $invoice)
    {
        $tax_items = $this->getTaxItems();

        /** @var TaxesFiller $tax_filler */
        $tax_filler = Mockery::mock(TaxesFiller::class);

        $tax_filler->shouldReceive('calculate')->once()
            ->with(Mockery::on(function ($arg) use ($invoice) {
                return $arg instanceof Invoice && $arg->id == $invoice->id;
            }))->andReturn($tax_items);

        return $tax_filler;
    }
}
