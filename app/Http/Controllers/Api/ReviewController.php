<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalCase;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Submit a review and rating for a completed case.
     *
     * POST /api/v1/cases/{id}/review
     */
    public function store(Request $request, $id): JsonResponse
    {
        $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.required' => 'Rating (1-5) wajib diberikan.',
            'rating.min'      => 'Rating minimal adalah 1 bintang.',
            'rating.max'      => 'Rating maksimal adalah 5 bintang.',
            'comment.max'     => 'Komentar maksimal 1000 karakter.',
        ]);

        $case = LegalCase::findOrFail($id);
        $user = $request->user();

        // 1. Ensure the user is the client of the case
        if ($case->client_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak memberikan review untuk kasus ini.',
            ], 403);
        }

        // 2. Ensure the case is completed
        if ($case->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Review hanya dapat diberikan untuk kasus yang telah selesai.',
            ], 422);
        }

        // 3. Ensure a review hasn't been submitted yet
        if (Review::where('case_id', $case->id)->where('client_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah memberikan review untuk kasus ini sebelumnya.',
            ], 422);
        }

        // 4. Create the review
        // (This will trigger the ReviewObserver to update the expert's rating)
        $review = Review::create([
            'case_id'   => $case->id,
            'client_id' => $user->id,
            'expert_id' => $case->expert_id,
            'rating'    => $request->input('rating'),
            'comment'   => $request->input('comment'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih! Review Anda berhasil disimpan.',
            'data'    => $review,
        ], 201);
    }
}
