<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadDocumentRequest;
use App\Models\CaseDocument;
use App\Models\LegalCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class CaseDocumentController extends Controller
{
    /**
     * Upload a document for a specific case.
     * 
     * POST /api/cases/{case_id}/documents
     */
    public function store(UploadDocumentRequest $request, $case_id): JsonResponse
    {
        $user = $request->user();
        
        // Ensure the case exists and the user has access to it
        // (Client owns the case, or Expert is assigned to it)
        $case = LegalCase::where(function($query) use ($user) {
            $query->where('client_id', $user->id)
                  ->orWhere('expert_id', $user->id);
        })->findOrFail($case_id);

        $file = $request->file('file');
        
        // Generate a unique filename: case_id/timestamp_originalName
        $originalName = $file->getClientOriginalName();
        $safeName     = preg_replace('/[^A-Za-z0-9\-\_\.]/', '_', $originalName);
        $fileName     = time() . '_' . $safeName;
        
        // Store in local/s3 storage inside a 'cases/{id}' directory
        $path = $file->storeAs("cases/{$case->id}", $fileName, 'public');

        // Save to database
        $document = CaseDocument::create([
            'case_id'       => $case->id,
            'uploaded_by'   => $user->id,
            'file_name'     => $originalName,
            'file_path'     => '/storage/' . $path,
            'file_type'     => $file->getMimeType() ?? 'application/octet-stream',
            'file_size'     => $file->getSize(),
            'document_type' => $request->input('document_type', 'other'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil diunggah.',
            'data'    => $document
        ], 201);
    }
}
