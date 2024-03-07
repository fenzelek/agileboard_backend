<?php

namespace App\Modules\Project\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Db\File;
use Illuminate\Validation\Rule;

class FileIndex extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $fileable_type = $this->input('fileable_type');

        $rules = [
            'fileable_type' => ['string', 'in:knowledge_pages,stories,tickets,none'],
            'file_type' => ['string', Rule::in(File::getListTypes())],
        ];

        if ($fileable_type && $fileable_type != 'none') {
            $rules['fileable_id'] = [
                Rule::exists($fileable_type, 'id')
                    ->where('project_id', $this->route('project')->id),
            ];
        }

        return $rules;
    }
}
