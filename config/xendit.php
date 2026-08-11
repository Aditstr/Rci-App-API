<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Xendit Secret API Key
    |--------------------------------------------------------------------------
    |
    | Your secret API key from the Xendit Dashboard.
    | For development/testing, use keys with the "xnd_development_" prefix.
    | No real money will be charged in development mode.
    |
    */
    'secret_key' => env('XENDIT_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Xendit Webhook Verification Token
    |--------------------------------------------------------------------------
    |
    | The callback verification token from Xendit Dashboard → Settings → Webhooks.
    | Used to verify that incoming webhook requests are genuinely from Xendit.
    |
    */
    'webhook_token' => env('XENDIT_WEBHOOK_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Invoice Duration (seconds)
    |--------------------------------------------------------------------------
    |
    | How long an invoice remains payable before it expires.
    | Default: 86400 seconds (24 hours).
    |
    */
    'invoice_duration' => env('XENDIT_INVOICE_DURATION', 86400),

    /*
    |--------------------------------------------------------------------------
    | Success / Failure Redirect URLs
    |--------------------------------------------------------------------------
    |
    | After a user completes or cancels payment on Xendit's page,
    | they will be redirected to these URLs (for web-based flows).
    |
    */
    'success_redirect_url' => env('XENDIT_SUCCESS_REDIRECT_URL', ''),
    'failure_redirect_url' => env('XENDIT_FAILURE_REDIRECT_URL', ''),

];
