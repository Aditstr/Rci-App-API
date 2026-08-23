<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ComplianceFlag;
use App\Models\LegalCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ComplianceFlagController extends Controller
{
    public function store(Request $request, int $caseId, int $messageId): JsonResponse
    {
        $case = LegalCase::findOrFail($caseId);
        Gate::authorize('message', $case);

        $reporter = $request->user();
        if (! $reporter->isClient() || $reporter->id !== $case->client_id) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya klien pada kasus ini yang dapat melaporkan ajakan pembayaran.',
            ], 403);
        }

        $message = ChatMessage::with('sender:id,role')
            ->where('case_id', $case->id)
            ->findOrFail($messageId);

        if (! $message->sender?->isExpert()) {
            return response()->json([
                'success' => false,
                'message' => 'Pesan ini tidak dapat dilaporkan melalui kategori pembayaran di luar RCI.',
            ], 422);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $alreadyReported = ComplianceFlag::where('message_id', $message->id)
            ->where('reporter_id', $reporter->id)
            ->where('source', ComplianceFlag::SOURCE_USER_REPORT)
            ->whereIn('status', ['pending', 'reviewing', 'confirmed'])
            ->exists();

        if ($alreadyReported) {
            return response()->json([
                'success' => false,
                'message' => 'Pesan ini sudah Anda laporkan dan sedang ditinjau.',
            ], 409);
        }

        $flag = ComplianceFlag::create([
            'case_id' => $case->id,
            'message_id' => $message->id,
            'reporter_id' => $reporter->id,
            'subject_user_id' => $message->sender_id,
            'type' => ComplianceFlag::TYPE_OFF_PLATFORM_PAYMENT,
            'source' => ComplianceFlag::SOURCE_USER_REPORT,
            'severity' => 'high',
            'risk_score' => 80,
            'matched_signals' => ['manual_client_report'],
            'evidence_text' => $message->message,
            'reporter_notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan diterima. Tim RCI akan meninjaunya tanpa memberi tahu expert pelapor Anda.',
            'data' => ['id' => $flag->id, 'status' => $flag->status],
        ], 201);
    }
}
