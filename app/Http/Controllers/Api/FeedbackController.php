<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', Rule::in(array_keys(Feedback::CATEGORIES))],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'email' => ['nullable', 'email', 'max:255'],
        ], [
            'category.required' => 'Pilih kategori masukan Anda.',
            'category.in' => 'Kategori masukan tidak valid.',
            'message.required' => 'Tuliskan kritik atau saran Anda.',
            'message.min' => 'Isi masukan setidaknya 10 karakter.',
            'message.max' => 'Isi masukan maksimal 5.000 karakter.',
            'email.email' => 'Masukkan alamat email yang valid.',
            'email.max' => 'Alamat email terlalu panjang.',
        ]);

        Feedback::create([
            ...$data,
            'user_id' => $request->user('sanctum')?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih. Kritik dan saran Anda sudah kami terima.',
        ], 201);
    }
}
