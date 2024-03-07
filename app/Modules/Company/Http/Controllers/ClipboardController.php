<?php

namespace App\Modules\Company\Http\Controllers;

use App\Helpers\ErrorCode;
use App\Models\Db\Clipboard;
use App\Modules\SaleInvoice\Services\Clipboard\FileManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\Paginator;
use Illuminate\Contracts\Auth\Guard;
use Symfony\Component\HttpFoundation\Response;

class ClipboardController extends Controller
{
    /**
     * @param Request $request
     * @param Paginator $paginator
     * @param Guard $auth
     *
     * @return JsonResponse
     */
    public function index(Request $request, Paginator $paginator, Guard $auth)
    {
        $clipboard = Clipboard::inCompany($auth->user());

        $clipboard = $paginator->get($clipboard->orderBy('id'), 'clipboard.index');

        return ApiResponse::responseOk($clipboard);
    }

    /**
     * @param Guard $auth
     * @param int $id
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(Guard $auth, $id)
    {
        $clipboard = Clipboard::inCompany($auth->user())->findOrFail($id);

        $file_manager = new FileManager($clipboard->company);

        if (! $file_manager->isExists($clipboard->file_name)) {
            return ApiResponse::responseError(ErrorCode::CLIPBOARD_NOT_FOUND_FILE, Response::HTTP_NOT_FOUND);
        }

        return response()->download($file_manager->getFullPath($clipboard->file_name));
    }
}
