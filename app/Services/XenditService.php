<?php

declare(strict_types=1);

namespace App\Services;

use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\InvoiceApi;
use Xendit\XenditSdkException;

class XenditService
{
    protected InvoiceApi $invoiceApi;

    public function __construct()
    {
        Configuration::setXenditKey(config('xendit.secret_key'));
        $this->invoiceApi = new InvoiceApi();
    }

    /**
     * Create a Xendit Invoice for top-up or payment.
     *
     * @param  string  $externalId   Unique reference ID (e.g., "TOPUP-{payment_id}")
     * @param  float   $amount       Amount in IDR
     * @param  string  $description  Description shown on the invoice page
     * @param  string  $payerEmail   Customer's email address
     * @param  array   $extraParams  Additional invoice parameters (optional)
     * @return array{invoice_id: string, invoice_url: string, expiry_date: string}
     *
     * @throws \RuntimeException if Xendit API call fails
     */
    public function createInvoice(
        string $externalId,
        float $amount,
        string $description,
        string $payerEmail,
        array $extraParams = [],
    ): array {
        $params = array_merge([
            'external_id'      => $externalId,
            'amount'           => $amount,
            'description'      => $description,
            'currency'         => 'IDR',
            'invoice_duration' => config('xendit.invoice_duration', 86400),
            'payer_email'      => $payerEmail,
        ], $extraParams);

        // Add redirect URLs if configured
        $successUrl = config('xendit.success_redirect_url');
        $failureUrl = config('xendit.failure_redirect_url');

        if ($successUrl) {
            $params['success_redirect_url'] = $successUrl;
        }
        if ($failureUrl) {
            $params['failure_redirect_url'] = $failureUrl;
        }

        try {
            $request = new CreateInvoiceRequest($params);
            $result  = $this->invoiceApi->createInvoice($request);

            return [
                'invoice_id'  => $result->getId(),
                'invoice_url' => $result->getInvoiceUrl(),
                'expiry_date' => $result->getExpiryDate()?->format('c') ?? null,
            ];
        } catch (XenditSdkException $e) {
            throw new \RuntimeException(
                "Gagal membuat invoice Xendit: {$e->getMessage()}",
                $e->getCode(),
                $e,
            );
        }
    }

    /**
     * Retrieve an existing invoice by its Xendit Invoice ID.
     *
     * @param  string  $invoiceId  The Xendit invoice ID
     * @return array   Invoice details from Xendit
     *
     * @throws \RuntimeException if retrieval fails
     */
    public function getInvoice(string $invoiceId): array
    {
        try {
            $result = $this->invoiceApi->getInvoiceById($invoiceId);

            return [
                'id'          => $result->getId(),
                'external_id' => $result->getExternalId(),
                'status'      => $result->getStatus(),
                'amount'      => $result->getAmount(),
                'paid_amount' => $result->getPaidAmount(),
                'paid_at'     => $result->getPaidAt()?->format('c') ?? null,
                'payment_method' => $result->getPaymentMethod(),
                'invoice_url' => $result->getInvoiceUrl(),
            ];
        } catch (XenditSdkException $e) {
            throw new \RuntimeException(
                "Gagal mengambil data invoice Xendit: {$e->getMessage()}",
                $e->getCode(),
                $e,
            );
        }
    }

    /**
     * Verify the webhook callback token from Xendit.
     *
     * @param  string  $callbackToken  The x-callback-token header value
     * @return bool
     */
    public function verifyWebhookToken(string $callbackToken): bool
    {
        $expectedToken = config('xendit.webhook_token');

        if (empty($expectedToken)) {
            // If no token configured, reject all webhooks for safety
            return false;
        }

        return hash_equals($expectedToken, $callbackToken);
    }
}
