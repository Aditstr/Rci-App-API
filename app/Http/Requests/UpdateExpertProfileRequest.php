<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpertProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only experts (paralegal/lawyer) can update their expert profile
        return $this->user() && $this->user()->isExpert();
    }

    public function rules(): array
    {
        return [
            'bio'                 => ['sometimes', 'nullable', 'string', 'max:2000'],
            'specialization_tags' => ['sometimes', 'array', 'max:10'],
            'specialization_tags.*' => ['string', 'max:50'],
            'experience_years'    => ['sometimes', 'integer', 'min:0', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'bio.max'                       => 'Bio maksimal 2000 karakter.',
            'specialization_tags.array'     => 'Spesialisasi harus berupa array.',
            'specialization_tags.max'       => 'Maksimal 10 tag spesialisasi.',
            'specialization_tags.*.string'  => 'Setiap tag spesialisasi harus berupa teks.',
            'specialization_tags.*.max'     => 'Setiap tag spesialisasi maksimal 50 karakter.',
            'experience_years.integer'      => 'Pengalaman harus berupa angka.',
            'experience_years.min'          => 'Pengalaman tidak boleh negatif.',
            'experience_years.max'          => 'Pengalaman maksimal 60 tahun.',
        ];
    }
}
