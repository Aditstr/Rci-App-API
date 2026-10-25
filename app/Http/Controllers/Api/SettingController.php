<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    /**
     * Settings the public frontend is allowed to read.
     */
    private const PUBLIC_KEYS = [
        'bank_destination',
        'paralegal_sop',
        'lawyer_sop',
    ];

    /**
     * Get a setting by key.
     *
     * GET /api/v1/settings/{key}
     */
    public function show(string $key): JsonResponse
    {
        if (! in_array($key, self::PUBLIC_KEYS, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found.',
            ], 404);
        }

        $setting = Setting::where('key', $key)->first();

        if (! $setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $setting->value,
        ]);
    }
}
