<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\JpkGenerator;

use App\Modules\SaleInvoice\Services\Jpk\JpkGenerator;
use Tests\TestCase;

class GetFileNameTest extends TestCase
{
    /** @test */
    public function it_generates_valid_file_name_when_no_date_set()
    {
        $jpk_generator = app()->make(JpkGenerator::class);
        $this->assertSame('Jpk_FA__.xml', $jpk_generator->getFileName());
    }

    /** @test */
    public function it_generates_valid_file_name_when_both_start_date_and_end_date_set()
    {
        $jpk_generator = app()->make(JpkGenerator::class);
        $jpk_generator->setStartDate('2015-03-15')->setEndDate('2017-03-20');
        $this->assertSame('Jpk_FA_2015-03-15_2017-03-20.xml', $jpk_generator->getFileName());
    }
}
