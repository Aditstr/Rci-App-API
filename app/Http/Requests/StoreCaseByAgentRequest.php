<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCaseByAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ensure the authenticated user is a paralegal
        return $this->user() && $this->user()->isParalegal();
    }

    public function rules(): array
    {
        return [
            'client_name'  => ['required', 'string', 'max:255'],
            'client_phone' => ['required', 'string', 'max:20'],
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string', 'max:10000'],
            'category'     => ['required', 'string'],
            'amount'       => ['required', 'numeric', 'min:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_name.required'  => 'Nama klien wajib diisi.',
            'client_name.max'       => 'Nama klien maksimal 255 karakter.',
            'client_phone.required' => 'Nomor telepon klien wajib diisi.',
            'client_phone.max'      => 'Nomor telepon klien maksimal 20 karakter.',
            'title.required'        => 'Judul kasus wajib diisi.',
            'title.max'             => 'Judul kasus maksimal 255 karakter.',
            'description.required'  => 'Deskripsi kasus wajib diisi.',
            'category.required'     => 'Kategori kasus wajib dipilih.',
            'amount.required'       => 'Jumlah pembayaran/escrow wajib diisi.',
            'amount.numeric'        => 'Jumlah pembayaran harus berupa angka.',
            'amount.min'            => 'Jumlah pembayaran minimal Rp 1.000.',
        ];
    }
}
