<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\JpkBuilder;

use App\Models\Db\Company as CompanyModel;
use App\Models\Other\SaleInvoice\Jpk\Element;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Company;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Header;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceControl;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItem;
use App\Modules\SaleInvoice\Services\Jpk\Elements\InvoiceItemControl;
use App\Modules\SaleInvoice\Services\Jpk\Elements\Root;
use App\Modules\SaleInvoice\Services\Jpk\Elements\TaxRates;
use App\Modules\SaleInvoice\Services\Jpk\JpkBuilder;
use App\Modules\SaleInvoice\Services\Jpk\XmlBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;
use App\Models\Db\Invoice as InvoiceModel;
use App\Models\Db\InvoiceItem as InvoiceItemModel;

class CreateTest extends TestCase
{
    /** @test */
    public function it_runs_valid_creators_and_return_valid_data_when_no_invoices()
    {
        $root = Mockery::mock(Root::class);
        $header = Mockery::mock(Header::class);
        $company = Mockery::mock(Company::class);
        $invoice = Mockery::mock(Invoice::class);
        $invoice_control = Mockery::mock(InvoiceControl::class);
        $tax_rates = Mockery::mock(TaxRates::class);
        $invoice_item = Mockery::mock(InvoiceItem::class);
        $invoice_item_control = Mockery::mock(InvoiceItemControl::class);
        $xml_builder = Mockery::mock(XmlBuilder::class);

        $jpk_builder = new JpkBuilder(
            $root,
            $header,
            $company,
            $invoice,
            $invoice_control,
            $tax_rates,
            $invoice_item,
            $invoice_item_control,
            $xml_builder
        );

        $company_model = new CompanyModel();
        $company_model->id = 4212;

        $invoices = new Collection([]);
        $start_date = '2017-01-01';
        $end_date = '2017-12-08';

        $root_element = new Element('abc:root', 1);
        $header_element = new Element('def:header', 2);
        $company_element = new Element('ghi:company', 3);
        $invoice_control_element = new Element('jkl:invoices_control', 4);
        $tax_rates_element = new Element('mno:tax_rates', 5);
        $invoice_item_control_element = new Element('pqr:invoice_item_control', 6);

        $xml_content = 'this is sample xml content';

        $root->shouldReceive('create')->once()->withNoArgs()->andReturn($root_element);
        $header->shouldReceive('create')->once()
            ->with(Mockery::on(function ($arg) use ($company_model) {
                return $arg instanceof CompanyModel && $arg->id == $company_model->id;
            }), Mockery::on(function ($arg) use ($start_date) {
                return $arg instanceof Carbon && $arg->toDateString() == $start_date;
            }), Mockery::on(function ($arg) use ($end_date) {
                return $arg instanceof Carbon && $arg->toDateString() == $end_date;
            }))->andReturn($header_element);
        $company->shouldReceive('create')->once()
            ->with(Mockery::on(function ($arg) use ($company_model) {
                return $arg instanceof CompanyModel && $arg->id == $company_model->id;
            }))->andReturn($company_element);
        $invoice->shouldNotReceive('create');

        $invoice_control->shouldReceive('create')->once()
            ->with(Mockery::on(function ($arg) use ($invoices) {
                return $arg instanceof Collection && $arg->count() == $invoices->count();
            }))->andReturn($invoice_control_element);

        $tax_rates->shouldReceive('create')->once()->withNoArgs()->andReturn($tax_rates_element);

        $invoice_item->shouldNotReceive('create');
        $invoice_item_control->shouldReceive('create')->once()
            ->with(Mockery::on(function ($arg) use ($invoices) {
                return $arg instanceof Collection && $arg->count() == $invoices->count();
            }))->andReturn($invoice_item_control_element);

        $expected_element = clone $root_element;
        $expected_element->addChild($header_element);
        $expected_element->addChild($company_element);
        $expected_element->addChild($invoice_control_element);
        $expected_element->addChild($tax_rates_element);
        $expected_element->addChild($invoice_item_control_element);

        $xml_builder->shouldReceive('create')->once()
            ->with(Mockery::on(function ($arg) use ($expected_element) {
                $this->assertTrue($arg instanceof Element);
                $this->assertEquals($expected_element->toArray(), $arg->toArray());

                return true;
            }))->andReturn($xml_content);

        $result = $jpk_builder->create($company_model, $invoices, $start_date, $end_date);

        $this->assertSame($xml_content, $result);
    }

    /** @test */
    public function it_runs_valid_creators_and_return_valid_data_when_there_are_invoices()
    {
        $root = Mockery::mock(Root::class);
        $header = Mockery::mock(Header::class);
        $company = Mockery::mock(Company::class);
        $invoice = Mockery::mock(Invoice::class);
        $invoice_control = Mockery::mock(InvoiceControl::class);
        $tax_rates = Mockery::mock(TaxRates::class);
        $invoice_item = Mockery::mock(InvoiceItem::class);
        $invoice_item_control = Mockery::mock(InvoiceItemControl::class);
        $xml_builder = Mockery::mock(XmlBuilder::class);

        $jpk_builder = new JpkBuilder(
            $root,
            $header,
            $company,
            $invoice,
            $invoice_control,
            $tax_rates,
            $invoice_item,
            $invoice_item_control,
            $xml_builder
        );

        $company_model = new CompanyModel();
        $company_model->id = 4212;

        $invoice_item_1 = new InvoiceItemModel(['price_net_sum' => 5126171231]);
        $invoice_item_2 = new InvoiceItemModel(['price_net_sum' => 3123123]);
        $invoice_item_3 = new InvoiceItemModel(['price_net_sum' => -312351]);
        $invoice_item_4 = new InvoiceItemModel(['price_net_sum' => 9135723]);
        $invoice_item_5 = new InvoiceItemModel(['price_net_sum' => 9301784]);
        $invoice_item_6 = new InvoiceItemModel(['price_net_sum' => -17341]);
        $invoice_item_7 = new InvoiceItemModel(['price_net_sum' => 8918391]);
        $invoice_item_8 = new InvoiceItemModel(['price_net_sum' => 4619043]);

        $invoice_1 = new InvoiceModel(['price_gross' => 5728939212]);
        $invoice_1->setRelation('items', new Collection([
            $invoice_item_1,
            $invoice_item_2,
        ]));

        $invoice_2 = new InvoiceModel(['price_gross' => 5728939212]);
        $invoice_2->setRelation('items', new Collection([
            $invoice_item_3,
            $invoice_item_4,
            $invoice_item_5,
        ]));

        $invoice_3 = new InvoiceModel(['price_gross' => 5728939212]);
        $invoice_3->setRelation('items', new Collection([
            $invoice_item_6,
            $invoice_item_7,
            $invoice_item_8,
        ]));

        $invoices = new Collection([$invoice_1, $invoice_2, $invoice_3]);
        $start_date = '2017-01-01';
        $end_date = '2017-12-08';

        $root_element = new Element('abc:root', 1);
        $header_element = new Element('def:header', 2);
        $company_element = new Element('ghi:company', 3);
        $invoice_control_element = new Element('jkl:invoices_control', 4);
        $tax_rates_element = new Element('mno:tax_rates', 5);
        $invoice_item_control_element = new Element('pqr:invoice_item_control', 6);
        $invoice_1_element = new Element('tuv:invoice_1_element', 7);
        $invoice_2_element = new Element('wxy:invoice_2_element', 8);
        $invoice_3_element = new Element('zab:invoice_3_element', 9);
        $invoice_item_1_element = new Element('cde:invoice_item_1_element', 10);
        $invoice_item_2_element = new Element('fgh:invoice_item_2_element', 11);
        $invoice_item_3_element = new Element('ijk:invoice_item_3_element', 12);
        $invoice_item_4_element = new Element('lmn:invoice_item_4_element', 13);
        $invoice_item_5_element = new Element('opq:invoice_item_5_element', 14);
        $invoice_item_6_element = new Element('rst:invoice_item_6_element', 15);
        $invoice_item_7_element = new Element('uvw:invoice_item_7_element', 16);
        $invoice_item_8_element = new Element('xyz:invoice_item_8_element', 17);

        $xml_content = 'this is sample xml content';

        $root->shouldReceive('create')->once()->withNoArgs()->andReturn($root_element);
        $header->shouldReceive('create')->once()
            ->with(Mockery::on(function ($arg) use ($company_model) {
                return $arg instanceof CompanyModel && $arg->id == $company_model->id;
            }), Mockery::on(function ($arg) use ($start_date) {
                return $arg instanceof Carbon && $arg->toDateString() == $start_date;
            }), Mockery::on(function ($arg) use ($end_date) {
                return $arg instanceof Carbon && $arg->toDateString() == $end_date;
            }))->andReturn($header_element);
        $company->shouldReceive('create')->once()
            ->with(Mockery::on(function ($arg) use ($company_model) {
                return $arg instanceof CompanyModel && $arg->id == $company_model->id;
            }))->andReturn($company_element);

        // here we set expectations for invoices
        foreach (range(1, 3) as $index) {
            $invoice_object = ${'invoice_' . $index};
            $returned_invoice_element = ${'invoice_' . $index . '_element'};
            $invoice->shouldReceive('create')->once()
                ->with(Mockery::on(function ($arg) use ($invoice_object) {
                    return $arg instanceof InvoiceModel &&
                        $arg->price_gross == $invoice_object->price_gross;
                }), Mockery::on(function ($arg) use ($company_model) {
                    return $arg instanceof CompanyModel && $arg->id == $company_model->id;
                }))->andReturn($returned_invoice_element);
        }

        $invoice_control->shouldReceive('create')->once()
            ->with(Mockery::on(function ($arg) use ($invoices) {
                return $arg instanceof Collection && $arg->count() == $invoices->count();
            }))->andReturn($invoice_control_element);

        $tax_rates->shouldReceive('create')->once()->withNoArgs()->andReturn($tax_rates_element);

        // here we set expectations for invoices items
        foreach (range(1, 8) as $index) {
            $invoice_item_object = ${'invoice_item_' . $index};
            $returned_invoice_item_element = ${'invoice_item_' . $index . '_element'};

            // calculate to which invoice belongs this invoice item (1,2 => 1, 3..5 => 2, 6..8 => 3)
            $invoice_index = $index < 3 ? 1 : ($index < 6 ? 2 : 3);
            $invoice_object = ${'invoice_' . $invoice_index};

            $invoice_item->shouldReceive('create')->once()
                ->with(Mockery::on(function ($arg) use ($invoice_item_object) {
                    return $arg instanceof InvoiceItemModel &&
                        $arg->price_net_sum == $invoice_item_object->price_net_sum;
                }), Mockery::on(function ($arg) use ($invoice_object) {
                    return $arg instanceof InvoiceModel &&
                        $arg->price_gross == $invoice_object->price_gross;
                }))->andReturn($returned_invoice_item_element);
        }

        $invoice_item_control->shouldReceive('create')->once()
            ->with(Mockery::on(function ($arg) use ($invoices) {
                return $arg instanceof Collection && $arg->count() == $invoices->count();
            }))->andReturn($invoice_item_control_element);

        $expected_element = clone $root_element;
        $expected_element->addChild($header_element);
        $expected_element->addChild($company_element);
        $expected_element->addChild($invoice_1_element);
        $expected_element->addChild($invoice_2_element);
        $expected_element->addChild($invoice_3_element);
        $expected_element->addChild($invoice_control_element);
        $expected_element->addChild($tax_rates_element);
        $expected_element->addChild($invoice_item_1_element);
        $expected_element->addChild($invoice_item_2_element);
        $expected_element->addChild($invoice_item_3_element);
        $expected_element->addChild($invoice_item_4_element);
        $expected_element->addChild($invoice_item_5_element);
        $expected_element->addChild($invoice_item_6_element);
        $expected_element->addChild($invoice_item_7_element);
        $expected_element->addChild($invoice_item_8_element);
        $expected_element->addChild($invoice_item_control_element);

        $xml_builder->shouldReceive('create')->once()
            ->with(Mockery::on(function ($arg) use ($expected_element) {
                $this->assertTrue($arg instanceof Element);
                $this->assertEquals($expected_element->toArray(), $arg->toArray());

                return true;
            }))->andReturn($xml_content);

        $result = $jpk_builder->create($company_model, $invoices, $start_date, $end_date);

        $this->assertSame($xml_content, $result);
    }
}
