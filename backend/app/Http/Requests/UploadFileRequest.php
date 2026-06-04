<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|max:2048|mimes:jpg,png'
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'File không hợp lệ hoặc vượt quá 2MB',
            'file.max'   => 'File không hợp lệ hoặc vượt quá 2MB',
            'file.file'  => 'File không hợp lệ hoặc vượt quá 2MB',
        ];
    }
}
