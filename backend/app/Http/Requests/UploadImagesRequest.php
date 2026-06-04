<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images'   => 'required|array',
            'images.*' => 'file|max:2048|mimes:jpg,png'
        ];
    }

    public function messages(): array
    {
        return [
            'images.*.mimes' => 'File không hợp lệ hoặc vượt quá 2MB',
            'images.*.max'   => 'File không hợp lệ hoặc vượt quá 2MB',
            'images.*.file'  => 'File không hợp lệ hoặc vượt quá 2MB',
        ];
    }
}
