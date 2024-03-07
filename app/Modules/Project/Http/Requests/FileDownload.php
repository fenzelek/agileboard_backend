<?php

namespace App\Modules\Project\Http\Requests;

use App\Http\Requests\Request;

class FileDownload extends FileStore
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'width' => 'integer|min:50|max:500',
            'height' => 'integer|min:50|max:500',
        ];
    }
}
