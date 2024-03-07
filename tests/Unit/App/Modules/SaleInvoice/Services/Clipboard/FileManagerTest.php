<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Clipboard;

use App\Models\Db\Company;
use App\Modules\SaleInvoice\Services\Clipboard\FileManager;
use Tests\Unit\App\Modules\SaleInvoice\Services\Clipboard\Helpers\CreateInvoice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use niklasravnsborg\LaravelPdf\Pdf;
use Tests\TestCase;

class FileManagerTest extends TestCase
{
    use DatabaseTransactions, CreateInvoice;

    protected $preserveGlobalState = false;
    protected $runTestInSeparateProcess = true;

    private $file_manager;
    private $company;

    protected function setUp():void
    {
        parent::setUp();
        $this->company = new Company();
        $this->file_manager = new FileManager($this->company);
    }

    protected function tearDown():void
    {
        $this->app['filesystem']->deleteDirectory('clipboard');
        parent::tearDown();
    }

    /** @test */
    public function createDirectory_if_not_exists()
    {
        $file = new Pdf();
        $this->file_manager->save($file, 'sample_name');
        $this->assertDirectoryExists(implode(DIRECTORY_SEPARATOR, [
            config('filesystems.disks.local.root'),
            FileManager::CLIPBOARD_DIRECTORY,
            FileManager::PREFIX_DIRECTORY . $this->company->id,
        ]));
    }

    /** @test */
    public function is_exists_file_was_found_in_clipboard()
    {
        $filename = 'sample_name.ext';
        $filepath = $this->getStorageDiskFilepath($filename);
        $this->app['filesystem']->put($filepath, 'sample');
        $this->assertTrue($this->file_manager->isExists($filename));
    }

    /** @test */
    public function is_exists_file_was_not_found_in_clipboard()
    {
        $filename = 'sample_name.ext';
        $this->assertFalse($this->file_manager->isExists($filename));
    }

    /** @test */
    public function save_it_file_exists()
    {
        $filename = 'invoice_name.pdf';
        $this->file_manager->save('sample_content', $filename);
        $this->assertFileExists($this->getRootFilePath($filename));
    }

    /** @test */
    public function delete_existing_file()
    {
        $filename = 'sample_name.ext';

        $filepath = $this->getStorageDiskFilepath($filename);
        $this->app['filesystem']->put($filepath, 'sample');

        $this->file_manager->delete($filename);

        $this->assertFileDoesNotExist($this->getRootFilePath($filename));
    }

    /** @test */
    public function delete_no_delete_no_existing_file()
    {
        $filename = 'sample_name.ext';
        $this->file_manager->delete($filename);
        $this->assertFileDoesNotExist($this->getRootFilePath($filename));
    }

    /** @test */
    public function getFullPath_return_full_disk_path()
    {
        $filename = 'sample_name.ext';

        $filepath = $this->getStorageDiskFilepath($filename);
        $this->app['filesystem']->put($filepath, 'sample');

        $full_path_file = $this->file_manager->getFullPath($filename);

        $this->assertSame($this->getRootFilePath($filename), $full_path_file);
    }

    /** @test */
    public function getFullPath_return_path_also_if_file_not_exists()
    {
        $str = 'sample_name.ext';
        $full_path_file = $this->file_manager->getFullPath($str);
        $this->assertSame($this->getRootFilePath($str), $full_path_file);
    }
}
