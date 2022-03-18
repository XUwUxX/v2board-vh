<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class KnowledgeSave extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'category' => 'required',
            'language' => 'required',
            'title' => 'required',
            'body' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Tiêu đề không thể trống',
            'category.required' => 'Phân loại không thể trống',
            'body.required' => 'Nội dung không thể trống',
            'language.required' => 'Ngôn ngữ không thể trống'
        ];
    }
}
