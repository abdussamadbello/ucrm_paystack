<?php

namespace PaystackGateway;

use GuzzleHttp\Client;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\PluginLogManager;

class Controller
{
    private $api;
    private $config;
    private $logger;
    private $httpClient;

    public function __construct()
    {
        $this->api = UcrmApi::create();
        $this->config = PluginConfigManager::create();
        $this->logger = PluginLogManager::create();
        $this->httpClient = new Client();
    }

    /**
     * Initialize Paystack transaction for an invoice
     */
    public function initTransaction(int $invoiceId): array
    {
        try {
            // Get invoice details
            $invoice = $this->api->get("invoices/{$invoiceId}");
            if (!$invoice || $invoice['status'] !== 'unpaid') {
                throw new \Exception('Invoice not found or already paid');
            }

            $client = $this->api->get("clients/{$invoice['clientId']}");
            $amount = (int) ($invoice['total'] * 100); // Convert to kobo
            $reference = 'uisp-' . $invoiceId . '-' . time();

            $payload = [
                'email' => $client['contacts'][0]['email'] ?? $client['companyContactEmail'],
                'amount' => $amount,
                'currency' => 'NGN',
                'reference' => $reference,
                'callback_url' => $this->getCallbackUrl(),
                'metadata' => [
                    'invoice_id' => $invoiceId,
                    'client_id' => $invoice['clientId']
                ]
            ];

            $response = $this->httpClient->post($this->getPaystackUrl('/transaction/initialize'), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getSecretKey(),
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload
            ]);

            $result = json_decode($response->getBody(), true);
            
            if (!$result['status']) {
                throw new \Exception('Paystack initialization failed: ' . $result['message']);
            }

            $this->logger->info("Transaction initialized for invoice {$invoiceId}", [
                'reference' => $reference,
                'amount' => $amount
            ]);

            return $result['data'];

        } catch (\Exception $e) {
            $this->logger->error("Failed to initialize transaction: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify transaction with Paystack
     */
    public function verifyTransaction(string $reference): array
    {
        $response = $this->httpClient->get($this->getPaystackUrl("/transaction/verify/{$reference}"), [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getSecretKey()
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Mark invoice as paid in UCRM
     */
    public function markInvoicePaid(int $invoiceId, array $transactionData): void
    {
        $payment = [
            'method' => 'Paystack',
            'amount' => $transactionData['amount'] / 100, // Convert from kobo
            'note' => 'Paystack payment - Ref: ' . $transactionData['reference'],
            'invoiceIds' => [$invoiceId]
        ];

        $this->api->post("invoices/{$invoiceId}/payments", $payment);
        
        $this->logger->info("Invoice {$invoiceId} marked as paid", [
            'reference' => $transactionData['reference'],
            'amount' => $payment['amount']
        ]);
    }

    private function getSecretKey(): string
    {
        $testMode = $this->config->get('testMode', false);
        return $testMode ? 'sk_test_...' : $this->config->get('skLive');
    }

    private function getPublicKey(): string
    {
        $testMode = $this->config->get('testMode', false);
        return $testMode ? 'pk_test_...' : $this->config->get('pkLive');
    }

    private function getPaystackUrl(string $endpoint): string
    {
        return 'https://api.paystack.co' . $endpoint;
    }

    private function getCallbackUrl(): string
    {
        return $this->api->getAppUrl() . '/plugins/paystack-gateway/public/callback.php';
    }
} 