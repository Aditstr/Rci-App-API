<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Models\ComplianceFlag;
use App\Models\LegalCase;
use App\Services\OffPlatformPaymentDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ChatController extends Controller
{
    public function __construct(
        protected OffPlatformPaymentDetector $paymentDetector,
    ) {}

    /**
     * Get chat messages for a specific legal case.
     *
     * GET /api/cases/{id}/messages
     */
    public function index(Request $request, int $caseId): JsonResponse
    {
        $case = LegalCase::findOrFail($caseId);
        $user = $request->user();

        Gate::authorize('message', $case);

        $messages = ChatMessage::with([
            'sender:id,name,role',
            'complianceFlags' => fn ($query) => $query
                ->where('reporter_id', $user->id)
                ->where('source', ComplianceFlag::SOURCE_USER_REPORT),
        ])
            ->where('case_id', $case->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => ChatMessageResource::collection($messages),
        ]);
    }

    /**
     * Send a new chat message to a specific legal case.
     *
     * POST /api/cases/{id}/messages
     */
    public function store(Request $request, int $caseId): JsonResponse
    {
        $case = LegalCase::findOrFail($caseId);
        $user = $request->user();

        Gate::authorize('message', $case);

        $validated = $request->validate([
            'message'     => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*'=> ['string', 'url'],
        ]);

        $analysis = $user->isExpert()
            ? $this->paymentDetector->analyze($validated['message'])
            : ['score' => 0, 'severity' => 'low', 'signals' => [], 'should_flag' => false, 'should_block' => false];

        if ($analysis['should_block']) {
            ComplianceFlag::create([
                'case_id' => $case->id,
                'subject_user_id' => $user->id,
                'type' => ComplianceFlag::TYPE_OFF_PLATFORM_PAYMENT,
                'source' => ComplianceFlag::SOURCE_AUTOMATIC,
                'severity' => $analysis['severity'],
                'risk_score' => $analysis['score'],
                'matched_signals' => $analysis['signals'],
                'evidence_text' => $validated['message'],
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => false,
                'code' => 'OFF_PLATFORM_PAYMENT_BLOCKED',
                'message' => 'Pesan tidak dikirim karena terdeteksi mengarahkan pembayaran di luar RCI. Gunakan penawaran, dompet, dan escrow resmi RCI.',
            ], 422);
        }

        $chatMessage = DB::transaction(function () use ($case, $user, $validated, $analysis) {
            $message = ChatMessage::create([
                'case_id'     => $case->id,
                'sender_id'   => $user->id,
                'message'     => $validated['message'],
                'attachments' => $validated['attachments'] ?? [],
            ]);

            if ($analysis['should_flag']) {
                ComplianceFlag::create([
                    'case_id' => $case->id,
                    'message_id' => $message->id,
                    'subject_user_id' => $user->id,
                    'type' => ComplianceFlag::TYPE_OFF_PLATFORM_PAYMENT,
                    'source' => ComplianceFlag::SOURCE_AUTOMATIC,
                    'severity' => $analysis['severity'],
                    'risk_score' => $analysis['score'],
                    'matched_signals' => $analysis['signals'],
                    'evidence_text' => $validated['message'],
                    'status' => 'pending',
                ]);
            }

            return $message;
        });

        $chatMessage->load('sender:id,name,role');

        try {
            \App\Events\ChatMessageSent::dispatch($chatMessage);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Broadcasting failed: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Pesan berhasil dikirim.',
            'data'    => new ChatMessageResource($chatMessage),
        ], 201);
    }

    /**
     * Mark unread messages in the case chat room as read.
     * Only marks messages that were NOT sent by the current user.
     *
     * PUT /api/cases/{id}/messages/read
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        $case = LegalCase::findOrFail($id);
        $user = $request->user();

        Gate::authorize('message', $case);

        // Mark messages as read where sender_id != current user
        ChatMessage::where('case_id', $case->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Pesan telah ditandai sebagai dibaca.',
        ]);
    }
}
