<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RciManualTopupController extends Controller
{
    /**
     * Submit manual top-up with proof image.
     * POST /api/v1/rci/topup/manual
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000|max:100000000',
            'bank_name' => 'required|string|max:50',
            'sender_name' => 'required|string|max:100',
            'proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $user = $request->user();
        $amountStr = number_format((float) $request->amount, 2, '.', '');

        // ensure wallet exists
        Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => '0.00']);

        $payment = Payment::create([
            'user_id' => $user->id,
            'payment_type' => 'topup',
            'amount' => $amountStr,
            'currency' => 'IDR',
            'status' => 'pending_proof',
            'payment_method' => 'manual_transfer',
            'bank_name' => $request->bank_name,
            'sender_name' => $request->sender_name,
            'metadata' => [
                'bank_destination' => optional(\App\Models\Setting::where('key', 'bank_destination')->first())->value,
            ],
        ]);

        // store proof to s3 or local private disk
        $disk = config('filesystems.default', 'local');
        // prefer s3 if configured, fallback to local
        if ($disk === 's3' || config('filesystems.disks.s3.key')) {
            $disk = 's3';
        } else {
            $disk = 'local';
        }

        $ext = $request->file('proof')->getClientOriginalExtension();
        $path = "manual-proofs/{$user->id}/{$payment->id}_" . time() . ".{$ext}";
        $storedPath = Storage::disk($disk)->put($path, file_get_contents($request->file('proof')->getRealPath()));

        // store actual path returned (for s3 put returns true, so use $path)
        $payment->update(['proof_path' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Bukti transfer berhasil dikirim. Menunggu verifikasi admin (maks 1x24 jam).',
            'data' => [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'bank_name' => $payment->bank_name,
                'sender_name' => $payment->sender_name,
            ],
        ], 201);
    }

    /**
     * List user's manual top-ups
     * GET /api/v1/rci/topup/manual
     */
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::where('user_id', $request->user()->id)
            ->where('payment_type', 'topup')
            ->where('payment_method', 'manual_transfer')
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->get('per_page', 15), 50));

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }
}
