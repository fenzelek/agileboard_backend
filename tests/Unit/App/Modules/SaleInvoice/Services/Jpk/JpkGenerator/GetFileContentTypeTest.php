<?php

namespace Tests\Unit\App\Modules\SaleInvoice\Services\Jpk\JpkGenerator;

use App\Modules\SaleInvoice\Services\Jpk\JpkGenerator;
use Tests\TestCase;

class GetFileContentTypeTest extends TestCase
{
    /** @test */
    public function it_returns_valid_content_type()
    {
        $jpk_generator = app()->make(JpkGenerator::class);
        $this->assertSame('text/xml', $jpk_generator->getFileContentType());
    }
}
