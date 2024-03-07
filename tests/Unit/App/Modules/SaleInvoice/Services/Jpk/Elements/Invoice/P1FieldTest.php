<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\Elements\Invoice;

use Tests\Helpers\Jpk;
use Tests\TestCase;

class P1FieldTest extends TestCase
{
    use Jpk;

    /** @test */
    public function it_sets_valid_issue_date()
    {
        $issue_date = '2017-03-12';

        $invoice = $this->getDefaultInvoiceModel();
        $invoice->issue_date = $issue_date;

        $result = $this->buildAndCreateResult($invoice);

        $this->findAndVerifyField($result, 'tns:P_1', $issue_date);
    }
}
