<?php

namespace App\Modules\SaleInvoice\Services\Clipboard;

use App\Models\Db\Company;
use Illuminate\Filesystem\FilesystemManager;

class FileManager
{
    const CLIPBOARD_DIRECTORY = 'clipboard';
    const PREFIX_DIRECTORY = 'company_id_';
    /**
     * @var Company
     */
    private $company;

    public function __construct(Company $company)
    {
        $this->storage = app()->make(FilesystemManager::class);
        $this->company = $company;
    }

    public function save($file, $file_name)
    {
        $this->storage->put($this->getDiskPathFile($file_name), $file);
    }

    public function isExists($file_name)
    {
        if (empty($file_name)) {
            return false;
        }

        return $this->storage->exists($this->getDiskPathFile($file_name));
    }

    public function delete($file_name)
    {
        if (! $this->isExists($file_name)) {
            return;
        }
        $this->storage->delete($this->getDiskPathFile($file_name));
    }

    public function getFullPath($file_name)
    {
        return $this->storage->path(($this->getDiskPathFile($file_name)));
    }

    /**
     * @param $file_name
     * @param $path
     * @return string
     */
    public function getDiskPathFile($file_name): string
    {
        return $this->getOwnDirectory() . DIRECTORY_SEPARATOR . $file_name;
    }

    protected function getOwnDirectoryPath()
    {
        return implode(DIRECTORY_SEPARATOR, [
            self::CLIPBOARD_DIRECTORY,
            self::PREFIX_DIRECTORY . $this->company->id,
        ]);
    }

    protected function getOwnDirectory()
    {
        $path = $this->getOwnDirectoryPath();
        if (! $this->storage->exists($path)) {
            $this->storage->makeDirectory($path);
        }

        return $path;
    }
}
