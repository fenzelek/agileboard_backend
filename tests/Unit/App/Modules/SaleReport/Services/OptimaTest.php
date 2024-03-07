<?php

namespace Tests\Unit\App\Modules\SaleReport\Services;

use App\Models\Db\Invoice;
use App\Modules\SaleReport\Services\Optima;
use App\Modules\SaleReport\Services\Optima\InvoiceFiller;
use App\Modules\SaleReport\Services\Optima\MazoviaConverter;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class OptimaTest extends TestCase
{
    /** @test */
    public function getFileContent_it_returns_empty_string_for_invoices()
    {
        $optima = app()->make(Optima::class);

        $this->assertSame('', $optima->getFileContent(new Collection()));
    }

    /** @test */
    public function getFileContent_it_returns_properly_formatted_csv_file_for_single_invoice()
    {
        $invoice = new Invoice();
        $invoice->id = 17;

        $invoice_fields = ['col1' => 'TEST', 'col2' => 'OTHER'];
        $expected_output = '"TEST","OTHER"';
        $mazovia_output = 'mazoviaOutput';

        /** @var InvoiceFiller $invoice_filler */
        $invoice_filler = Mockery::mock(InvoiceFiller::class);

        $invoice_filler->shouldReceive('getFields')->once()
            ->with(Mockery::on(function ($arg) use ($invoice) {
                return $arg instanceof Invoice && $arg->id == $invoice->id;
            }))->andReturn($invoice_fields);

        /** @var MazoviaConverter $mazovia */
        $mazovia = Mockery::mock(MazoviaConverter::class);
        $mazovia->shouldReceive('fromUtf8')->once()->with($expected_output)
            ->andReturn($mazovia_output);

        $optima = new Optima($invoice_filler, $mazovia);
        $this->assertSame($mazovia_output, $optima->getFileContent(new Collection([$invoice])));
    }

    /** @test */
    public function getFileContent_it_returns_properly_formatted_csv_file_for_two_invoices()
    {
        $invoice = new Invoice();
        $invoice->id = 17;
        $invoice_fields = ['col1' => 'TEST', 'col2' => 'OTHER'];

        $invoice2 = new Invoice();
        $invoice2->id = 19;
        $invoice2_fields = ['col1' => 'COMPLETELY', 'col2' => 'DIFFERENT'];

        $expected_output = '"TEST","OTHER"' . "\r\n" . '"COMPLETELY","DIFFERENT"';
        $mazovia_output = 'mazoviaOutput';

        /** @var InvoiceFiller $invoice_filler */
        $invoice_filler = Mockery::mock(InvoiceFiller::class);

        $invoice_filler->shouldReceive('getFields')->once()
            ->with(Mockery::on(function ($arg) use ($invoice) {
                return $arg instanceof Invoice && $arg->id == $invoice->id;
            }))->andReturn($invoice_fields);

        $invoice_filler->shouldReceive('getFields')->once()
            ->with(Mockery::on(function ($arg) use ($invoice2) {
                return $arg instanceof Invoice && $arg->id == $invoice2->id;
            }))->andReturn($invoice2_fields);

        /** @var MazoviaConverter $mazovia */
        $mazovia = Mockery::mock(MazoviaConverter::class);
        $mazovia->shouldReceive('fromUtf8')->once()->with($expected_output)
            ->andReturn($mazovia_output);

        $optima = new Optima($invoice_filler, $mazovia);
        $this->assertSame(
            $mazovia_output,
            $optima->getFileContent(new Collection([$invoice, $invoice2]))
        );
    }

    /** @test */
    public function getFileContent_it_properly_formats_different_csv_columns()
    {
        $invoice = new Invoice();
        $invoice->id = 17;

        $invoice_fields = [
            'col1' => 'TEST',
            'col2' => 15,
            'col3' => 15.22,
            'col4' => '15.13',
            'col5' => str_repeat('w', 300),
        ];
        $expected_output = '"TEST",15,15.22,15.13,"' . str_repeat('w', 255) . '"';
        $mazovia_output = 'mazoviaOutput';

        /** @var InvoiceFiller $invoice_filler */
        $invoice_filler = Mockery::mock(InvoiceFiller::class);

        $invoice_filler->shouldReceive('getFields')->once()
            ->with(Mockery::on(function ($arg) use ($invoice) {
                return $arg instanceof Invoice && $arg->id == $invoice->id;
            }))->andReturn($invoice_fields);

        /** @var MazoviaConverter $mazovia */
        $mazovia = Mockery::mock(MazoviaConverter::class);
        $mazovia->shouldReceive('fromUtf8')->once()->with($expected_output)
            ->andReturn($mazovia_output);

        $optima = new Optima($invoice_filler, $mazovia);
        $this->assertSame($mazovia_output, $optima->getFileContent(new Collection([$invoice])));
    }

    /** @test */
    public function getFileContentType_it_returns_valid_type()
    {
        $optima = app()->make(Optima::class);

        $this->assertSame('text/plain', $optima->getFileContentType());
    }

    /** @test */
    public function getFileName_it_returns_valid_name()
    {
        $optima = app()->make(Optima::class);

        $this->assertSame('VAT_R.TXT', $optima->getFileName());
    }
}
