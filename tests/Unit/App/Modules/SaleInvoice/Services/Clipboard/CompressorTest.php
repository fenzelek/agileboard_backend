<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Clipboard;

use App\Models\Db\Company;
use App\Modules\SaleInvoice\Services\Clipboard\Compressor;
use App\Modules\SaleInvoice\Services\Clipboard\FileManager;
use Carbon\Carbon;
use Tests\Unit\App\Modules\SaleInvoice\Services\Clipboard\Helpers\CreateInvoice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CompressorTest extends TestCase
{
    use DatabaseTransactions, CreateInvoice;

    private $compressor;
    private $company;
    private $file_manager;
    private $now;

    protected function setUp():void
    {
        parent::setUp();
        $this->company = new Company();
        $this->file_manager = new FileManager($this->company);
        $this->compressor = $this->app->make(Compressor::class);
        $this->now = Carbon::now();
        Carbon::setTestNow($this->now);
    }

    protected function tearDown():void
    {
        $this->app['filesystem']->deleteDirectory('clipboard');
        parent::tearDown();
    }

    /** @test */
    public function zip_package_was_created()
    {
        $filename = 'sample_name.txt';

        $filepath = $this->getStorageDiskFilepath($filename);
        $this->app['filesystem']->put($filepath, 'sample');

        $this->compressor->zip($this->file_manager, [$filename]);

        $zip_file_name = Compressor::PREFIX . $this->now->format('Y-m-d_h_i_s') . '.' . Compressor::EXT;
        $this->assertFileExists($this->getRootFilePath($zip_file_name));
    }

    /** @test */
    public function zip_package_was_not_created_with_empty_files()
    {
        $this->compressor->zip($this->file_manager, []);

        $zip_file_name = Compressor::PREFIX . $this->now->format('Y-m-d_h_i_s') . '.' . Compressor::EXT;
        $this->assertFileDoesNotExist($this->getRootFilePath($zip_file_name));
    }

    /** @test */
    public function zip_package_was_created_with_empty_files()
    {
        $filename = 'sample_name.txt';

        $this->compressor->zip($this->file_manager, [$filename]);

        $zip_file_name = Compressor::PREFIX . $this->now->format('Y-m-d_h_i_s') . '.' . Compressor::EXT;
        $this->assertFileDoesNotExist($this->getRootFilePath($zip_file_name));
    }

    /** @test */
    public function zip_return_file_name()
    {
        $filename = 'sample_name.txt';

        $filepath = $this->getStorageDiskFilepath($filename);
        $this->app['filesystem']->put($filepath, 'sample');

        $zip_file_name = $this->compressor->zip($this->file_manager, [$filename]);

        $expected_zip_file_name = Compressor::PREFIX . $this->now->format('Y-m-d_h_i_s') . '.' . Compressor::EXT;

        $this->assertSame($expected_zip_file_name, $zip_file_name);
    }
}
