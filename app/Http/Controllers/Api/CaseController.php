<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    /**
     * Get the active or recent cases for the authenticated client.
     * Includes the assigned expert profile if available.
     *
     * GET /api/cases
     */
    public function index(Request $request): JsonResponse
    {
        $cases = LegalCase::with(['expert.expertProfile', 'documents'])
            ->where('client_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar kasus berhasil diambil.',
            'data'    => $cases,
        ]);
    }

    /**
     * Get a specific case detail for the tracking/dashboard view.
     *
     * GET /api/cases/{id}
     */
    public function show(Request $request, $id): JsonResponse
    {
        $case = LegalCase::with(['expert.expertProfile', 'documents'])
            ->where('client_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail kasus berhasil diambil.',
            'data'    => $case,
        ]);
    }

    /**
     * Submit a new case from the dashboard 'Ajukan Kasus Baru'.
     *
     * POST /api/cases
     */
    /**
     * Map Indonesian category names from frontend to English enum values in DB.
     */
    private function mapCategory(string $category): string
    {
        $map = [
            'hukum pidana'          => 'criminal',
            'pidana'                => 'criminal',
            'criminal'              => 'criminal',
            'hukum perdata'         => 'general',
            'perdata'               => 'general',
            'hukum keluarga'        => 'family',
            'keluarga'              => 'family',
            'family'                => 'family',
            'hukum perusahaan'      => 'corporate',
            'perusahaan'            => 'corporate',
            'korporasi'             => 'corporate',
            'corporate'             => 'corporate',
            'hukum properti'        => 'property',
            'properti'              => 'property',
            'property'              => 'property',
            'hukum ketenagakerjaan' => 'labor',
            'ketenagakerjaan'       => 'labor',
            'labor'                 => 'labor',
            'hukum imigrasi'        => 'immigration',
            'imigrasi'              => 'immigration',
            'immigration'           => 'immigration',
            'hukum kekayaan intelektual' => 'intellectual_property',
            'kekayaan intelektual'  => 'intellectual_property',
            'intellectual_property' => 'intellectual_property',
            'hukum pajak'           => 'tax',
            'pajak'                 => 'tax',
            'tax'                   => 'tax',
            'umum'                  => 'general',
            'general'               => 'general',
        ];

        return $map[strtolower(trim($category))] ?? 'general';
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'category'    => 'required|string',
        ]);

        $mappedCategory = $this->mapCategory($request->input('category'));

        $case = LegalCase::create([
            'case_number' => LegalCase::generateCaseNumber(),
            'client_id'   => $request->user()->id,
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
            'category'    => $mappedCategory,
            'status'      => 'submitted',
            'submitted_at'=> now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kasus berhasil diajukan.',
            'data'    => $case,
        ], 201);
    }
}
