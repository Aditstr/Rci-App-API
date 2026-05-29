<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file'          => ['required', 'file', 'max:10240'], // max 10MB
            'document_type' => ['nullable', 'string', 'in:evidence,legal_letter,contract,identification,other'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File wajib diunggah.',
            'file.file'     => 'Format upload tidak valid.',
            'file.max'      => 'Ukuran file maksimal 10MB.',
            'document_type.in' => 'Tipe dokumen tidak valid.',
        ];
    }
}
