<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Clipboard;

use App\Models\Db\Clipboard;
use App\Models\Db\Company;
use App\Models\Db\Invoice;
use App\Modules\SaleInvoice\Services\Clipboard\InvoicesPackage;
use Carbon\Carbon;
use Tests\Unit\App\Modules\SaleInvoice\Services\Clipboard\Helpers\CreateInvoice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvoicePackageTest extends TestCase
{
    use DatabaseTransactions, CreateInvoice;

    protected $preserveGlobalState = false;
    protected $runTestInSeparateProcess = true;

    private $company;
    private $now;
    private $invoice_package;
    private $invoices;

    protected function setUp():void
    {
        parent::setUp();
        $this->company = factory(Company::class)->create();
        $this->invoice_package = $this->app->make(InvoicesPackage::class);
        $this->now = Carbon::now();
        Carbon::setTestNow($this->now);
        $this->invoices = factory(Invoice::class, 2)->create();
        $this->invoices->each(function ($invoice) {
            $this->allDependencies($invoice);
            $invoice->setRelation('company', $this->company);
        });
    }

    protected function tearDown():void
    {
        $this->app['filesystem']->deleteDirectory('clipboard');
        parent::tearDown();
    }

    /** @test */
    public function build_create_package()
    {
        $this->invoice_package->build($this->invoices);
        $this->assertFileExists($this->getRootFilePath(''));
        $this->assertSame(1, count(\Storage::allFiles($this->getStorageDiskFilepath(''))));
    }

    /** @test */
    public function build_file_was_add_to_DB()
    {
        Clipboard::whereRaw('1=1')->delete();
        $this->invoice_package->build($this->invoices);
        $clipboard = Clipboard::first();
        $this->assertSame($this->company->id, $clipboard->company_id);
        $file_name = basename(\Storage::allFiles($this->getStorageDiskFilepath(''))[0]);
        $this->assertSame($file_name, $clipboard->file_name);
    }
}
