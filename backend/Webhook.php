<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PaystackGateway\Controller;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;
use Ubnt\UcrmPluginSdk\Service\PluginLogManager;

// Get webhook payload
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

$config = PluginConfigManager::create();
$logger = PluginLogManager::create();

try {
    // Verify webhook signature
    $secretKey = $config->get('testMode', false) ? 'sk_test_...' : $config->get('skLive');
    $expectedSignature = hash_hmac('sha512', $payload, $secretKey);
    
    if (!hash_equals($expectedSignature, $signature)) {
        $logger->warning('Invalid webhook signature');
        http_response_code(400);
        exit('Invalid signature');
    }

    $data = json_decode($payload, true);
    
    if ($data['event'] !== 'charge.success') {
        $logger->info('Ignoring webhook event: ' . $data['event']);
        http_response_code(200);
        exit('OK');
    }

    $transactionData = $data['data'];
    $reference = $transactionData['reference'];
    
    // Extract invoice ID from reference (format: uisp-{invoiceId}-{timestamp})
    if (!preg_match('/^uisp-(\d+)-\d+$/', $reference, $matches)) {
        $logger->warning('Invalid reference format: ' . $reference);
        http_response_code(400);
        exit('Invalid reference');
    }
    
    $invoiceId = (int) $matches[1];
    
    $controller = new Controller();
    
    // Verify transaction with Paystack API
    $verification = $controller->verifyTransaction($reference);
    
    if (!$verification['status'] || $verification['data']['status'] !== 'success') {
        $logger->warning('Transaction verification failed for reference: ' . $reference);
        http_response_code(400);
        exit('Verification failed');
    }
    
    // Mark invoice as paid
    $controller->markInvoicePaid($invoiceId, $verification['data']);
    
    $logger->info('Webhook processed successfully', [
        'reference' => $reference,
        'invoice_id' => $invoiceId
    ]);
    
    http_response_code(200);
    echo 'OK';

} catch (\Exception $e) {
    $logger->error('Webhook processing failed: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error';
} 