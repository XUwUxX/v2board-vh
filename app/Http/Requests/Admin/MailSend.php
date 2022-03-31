<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MailSend extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type' => 'required|in:1,2,3,4',
            'subject' => 'required',
            'content' => 'required',
            'receiver' => 'array'
        ];
    }

    public function messages()
    {
        return [
            'type.required' => 'Loại gửi không thể trống',
            'type.in' => 'Định dạng loại gửi không chính xác',
            'subject.required' => 'Chủ đề không thể trống',
            'content.required' => 'Nội dung không thể trống',
            'receiver.array' => 'Người nhận không được định dạng sai'
        ];
    }
}
