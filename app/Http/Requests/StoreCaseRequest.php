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

    /**
     * Normalize field names before validation.
     * Frontend sends 'case_type', backend uses 'category'.
     */
    protected function prepareForValidation(): void
    {
        // Accept 'case_type' from frontend as alias for 'category'
        if ($this->has('case_type') && !$this->has('category')) {
            $this->merge(['category' => $this->input('case_type')]);
        }
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
            'category.required'    => 'Jenis kasus wajib dipilih.',
        ];
    }
}
