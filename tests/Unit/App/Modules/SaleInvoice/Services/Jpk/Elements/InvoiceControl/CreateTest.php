<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceControl;

use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceControl;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;
use App\Models\Db\Invoice as InvoiceModel;

class CreateTest extends TestCase
{
    /** @test */
    public function it_creates_element_of_valid_type()
    {
        $invoice_control = new InvoiceControl();
        $element = $invoice_control->create(new Collection());
        $this->assertTrue($element instanceof Element);
    }

    /** @test */
    public function it_creates_element_with_valid_content_when_no_invoices()
    {
        $invoice_control = new InvoiceControl();
        $element = $invoice_control->create(new Collection());

        $this->assertSame([
            'name' => 'tns:FakturaCtrl',
            'value' => null,
            'attributes' => [],
            'children' => [
                [
                    'name' => 'tns:LiczbaFaktur',
                    'value' => 0,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:WartoscFaktur',
                    'value' => '0.00',
                    'attributes' => [],
                    'children' => [],
                ],
            ],
        ], $element->toArray());
    }

    /** @test */
    public function it_creates_element_with_valid_content_when_there_is_single_invoice()
    {
        $invoice_control = new InvoiceControl();
        $element = $invoice_control->create(new Collection([
            new InvoiceModel(['price_gross' => 5728939212]),
        ]));

        $this->assertSame([
            'name' => 'tns:FakturaCtrl',
            'value' => null,
            'attributes' => [],
            'children' => [
                [
                    'name' => 'tns:LiczbaFaktur',
                    'value' => 1,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:WartoscFaktur',
                    'value' => '57289392.12',
                    'attributes' => [],
                    'children' => [],
                ],
            ],
        ], $element->toArray());
    }

    /** @test */
    public function it_creates_element_with_valid_content_when_there_are_multiple_invoice()
    {
        $invoice_control = new InvoiceControl();
        $element = $invoice_control->create(new Collection([
            new InvoiceModel(['price_gross' => 5728939212]),
            new InvoiceModel(['price_gross' => -41232331]),
            new InvoiceModel(['price_gross' => 23838316]),
        ]));

        $this->assertSame([
            'name' => 'tns:FakturaCtrl',
            'value' => null,
            'attributes' => [],
            'children' => [
                [
                    'name' => 'tns:LiczbaFaktur',
                    'value' => 3,
                    'attributes' => [],
                    'children' => [],
                ],
                [
                    'name' => 'tns:WartoscFaktur',
                    'value' => '57115451.97',
                    'attributes' => [],
                    'children' => [],
                ],
            ],
        ], $element->toArray());
    }
}
