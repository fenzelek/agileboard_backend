<?php

namespace App\Modules\SaleInvoice\Services\Clipboard;

use Carbon\Carbon;

class Compressor
{
    const PREFIX = 'faktury_';
    const EXT = 'zip';
    /**
     * @var \ZipArchive
     */
    private $zipArchive;

    public function __construct(\ZipArchive $zipArchive)
    {
        $this->zipArchive = $zipArchive;
    }

    public function zip(FileManager $file_manager, array $files)
    {
        $files = array_filter($files, function ($file) use ($file_manager) {
            return $file_manager->isExists($file);
        });
        if (empty($files)) {
            return;
        }
        $zip_name = self::PREFIX . Carbon::now()->format('Y-m-d_h_i_s') . '.' . self::EXT;

        $zip_file = $file_manager->getFullPath($zip_name);

        if (! $this->zipArchive->open($zip_file, \ZIPARCHIVE::CREATE | \ZIPARCHIVE::OVERWRITE)) {
            die("Failed to create archive\n");
        }

        foreach ($files as $file) {
            $this->zipArchive->addFile($file_manager->getFullPath($file), $file);
        }
        if (! $this->zipArchive->status == \ZIPARCHIVE::ER_OK) {
            echo "Failed to write local files to zip\n";
        }

        $this->zipArchive->close();

        return $zip_name;
    }
}
