<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'category'    => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => 'Judul kasus wajib diisi.',
            'title.max'            => 'Judul kasus maksimal 255 karakter.',
            'description.required' => 'Deskripsi kasus wajib diisi.',
            'category.required'    => 'Kategori kasus wajib dipilih.',
        ];
    }
}
