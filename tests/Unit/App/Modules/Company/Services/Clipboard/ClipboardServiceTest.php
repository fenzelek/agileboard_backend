<?php

namespace Tests\Unit\App\Modules\Company\Services\Clipboard;

use App\Models\Db\Clipboard;
use App\Models\Db\Company;
use App\Modules\Company\Exceptions\NoFileInClipboard;
use App\Modules\Company\Services\ClipBoardService;
use Carbon\Carbon;
use Tests\Unit\App\Modules\SaleInvoice\Services\Clipboard\Helpers\CreateInvoice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClipboardServiceTest extends TestCase
{
    use CreateInvoice, DatabaseTransactions;

    private $company;
    private $clipboard_service;
    private $now;

    protected function setUp():void
    {
        parent::setUp();
        $this->clipboard_service = $this->app->make(ClipBoardService::class);
        $this->company = factory(Company::class)->create();
        Clipboard::whereRaw('1=1')->delete();
        $this->now = Carbon::now();
        Carbon::setTestNow($this->now);
    }

    protected function tearDown():void
    {
        $this->app['filesystem']->deleteDirectory('clipboard');
        parent::tearDown();
    }

    /** @test */
    public function store_clipboard_was_stored_in_DB()
    {
        $file_name = 'sample_name.ext';
        $filepath = $this->getStorageDiskFilepath($file_name);
        $this->app['filesystem']->put($filepath, 'sample');

        $this->clipboard_service->store($this->company, $file_name);

        $raw_data = [
            'company_id' => $this->company->id,
            'file_name' => $file_name,
        ];
        $clipboard = Clipboard::latest('id')->first();
        $this->assertSame($this->company->id, $clipboard->company_id);
        $this->assertSame($file_name, $clipboard->file_name);
        $now = $this->now->micro(0);
        $this->assertEquals($now, $clipboard->created_at);
    }

    /** @test */
    public function delete_clipboard_from_DB()
    {
        $clipboard = Clipboard::create([
            'company_id' => $this->company->id,
        ]);
        Clipboard::create();
        $this->assertSame(2, Clipboard::count());
        $this->clipboard_service->delete($clipboard);
        $this->assertSame(1, Clipboard::count());
        $this->assertSame(0, $this->company->clipboard()->count());
    }

    /** @test */
    public function delete_file_was_remove()
    {
        $file_name = 'sample_name.ext';
        $filepath = $this->getStorageDiskFilepath($file_name);
        $this->app['filesystem']->put($filepath, 'sample');

        $clipboard = Clipboard::create([
            'company_id' => $this->company->id,
            'file_name' => $file_name,
        ]);
        $this->clipboard_service->delete($clipboard);
        $this->assertFileDoesNotExist($this->getRootFilePath($file_name));
    }

    /** @test */
    public function store_clipboard_was_not_stored_because_file_not_exists()
    {
        $this->expectException(NoFileInClipboard::class);
        $this->clipboard_service->store($this->company, 'sample_name.ext');
    }
}
