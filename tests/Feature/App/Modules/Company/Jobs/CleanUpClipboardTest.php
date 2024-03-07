<?php

namespace Tests\Feature\App\Modules\Company\Jobs;

use App\Models\Db\Clipboard;
use App\Models\Db\Company;
use App\Modules\Company\Jobs\CleanupClipboard;
use Carbon\Carbon;
use Tests\Unit\App\Modules\SaleInvoice\Services\Clipboard\Helpers\CreateInvoice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CleanUpClipboardTest extends TestCase
{
    use DatabaseTransactions, CreateInvoice;

    private $clean_up_clipboard;
    private $now;

    protected function setUp():void
    {
        parent::setUp();
        $this->clean_up_clipboard = $this->app->make(CleanupClipboard::class);
        $this->now = Carbon::now();
        Carbon::setTestNow($this->now);
    }

    protected function tearDown():void
    {
        $this->app['filesystem']->deleteDirectory('clipboard');
        parent::tearDown();
    }

    /** @test */
    public function handle_clean_up_old_files()
    {
        \Config::set('app_settings.cleanup_clipboard', 1);
        factory(Clipboard::class)->create([
            'created_at' => (clone $this->now)->subDays(2),
        ]);
        $this->assertSame(1, Clipboard::count());
        $this->clean_up_clipboard->handle();
        $this->assertSame(0, Clipboard::count());
    }

    /** @test */
    public function handle_leave_nowadays_files()
    {
        \Config::set('app_settings.cleanup_clipboard', 2);
        factory(Clipboard::class)->create([
            'created_at' => (clone $this->now)->subDays(1),
        ]);
        factory(Clipboard::class)->create([
            'created_at' => (clone $this->now)->subDays(3),
        ]);
        $this->assertSame(2, Clipboard::count());
        $this->clean_up_clipboard->handle();
        $this->assertSame(1, Clipboard::count());
    }

    /** @test */
    public function handle_file_was_deleted()
    {
        \Config::set('app_settings.cleanup_clipboard', 2);
        $this->company = factory(Company::class)->create();

        $filename = 'sample_name_new.ext';

        $filepath = $this->getStorageDiskFilepath($filename);
        $this->app['filesystem']->put($filepath, 'sample');

        $newest_clipboard = factory(Clipboard::class)->create([
            'created_at' => (clone $this->now)->subDays(1),
            'file_name' => $filename,
            'company_id' => $this->company->id,
        ]);

        $filename = 'sample_name_old.ext';

        $filepath = $this->getStorageDiskFilepath($filename);
        $this->app['filesystem']->put($filepath, 'sample');

        $old_clipboard = factory(Clipboard::class)->create([
            'created_at' => (clone $this->now)->subDays(3),
            'file_name' => $filename,
            'company_id' => $this->company->id,
        ]);
        $this->clean_up_clipboard->handle();

        $this->assertFileExists($this->getRootFilePath($newest_clipboard->file_name));
        $this->assertFileDoesNotExist($this->getRootFilePath($old_clipboard->file_name));
    }
}
