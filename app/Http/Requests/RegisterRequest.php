<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', 'string', 'in:client,paralegal,lawyer,corporate'],
        ];

        $role = $this->input('role');

        // ── Expert document upload moved to post-login Dashboard (Verification) ──
        // No file validation is required during initial registration anymore.

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Nama wajib diisi.',
            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Email sudah terdaftar.',
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'role.required'      => 'Role wajib dipilih.',
            'role.in'            => 'Role tidak valid. Pilih: client, paralegal, lawyer, atau corporate.',

            // Document uploads
            'ktp.required'          => 'Foto KTP wajib diupload.',
            'ktp.mimes'             => 'Format KTP harus JPG, PNG, atau PDF.',
            'ktp.max'               => 'Ukuran file KTP maksimal 5MB.',
            'ijazah.required'       => 'Scan ijazah wajib diupload.',
            'ijazah.mimes'          => 'Format ijazah harus JPG, PNG, atau PDF.',
            'ijazah.max'            => 'Ukuran file ijazah maksimal 5MB.',
            'license_card.required' => 'Kartu izin praktik (PERADI) wajib diupload.',
            'license_card.mimes'    => 'Format kartu izin harus JPG, PNG, atau PDF.',
            'license_card.max'      => 'Ukuran file kartu izin maksimal 5MB.',
            'selfie.required'       => 'Foto selfie wajib diupload untuk verifikasi identitas.',
            'selfie.mimes'          => 'Format selfie harus JPG atau PNG.',
            'selfie.max'            => 'Ukuran file selfie maksimal 5MB.',
            'cv.mimes'              => 'Format CV harus PDF, DOC, atau DOCX.',
            'cv.max'                => 'Ukuran file CV maksimal 10MB.',
        ];
    }
}
