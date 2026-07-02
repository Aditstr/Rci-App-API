<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Models\LegalCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChatController extends Controller
{
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

        // Fetch messages with sender info (name, role)
        // Adjust the selected fields based on your User model definition
        $messages = ChatMessage::with(['sender:id,name,role'])
            ->where('case_id', $case->id)
            ->orderBy('created_at', 'asc')
            ->cursorPaginate(50);

        return response()->json([
            'success' => true,
            'data'    => ChatMessageResource::collection($messages)->response()->getData(true),
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
            'attachments' => ['nullable', 'array', 'max:5'], // Optional: allow max 5 attachments
            'attachments.*'=> ['string', 'url'] // Expected as array of URLs (from prior upload) for simplicity
        ]);

        $chatMessage = ChatMessage::create([
            'case_id'     => $case->id,
            'sender_id'   => $user->id,
            'message'     => $validated['message'],
            'attachments' => $validated['attachments'] ?? [],
            'is_read'     => false,
            // 'read_at' => null // Will be handled dynamically later
        ]);

        // Eager load the sender detail for immediate response consistency
        $chatMessage->load('sender:id,name,role');

        // Broadcast the message event
        \App\Events\ChatMessageSent::dispatch($chatMessage);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.',
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
