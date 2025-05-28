<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PaystackGateway\Controller;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;
use Ubnt\UcrmPluginSdk\Service\PluginLogManager;

$api = UcrmApi::create();
$logger = PluginLogManager::create();
$controller = new Controller();

try {
    // Get all unpaid invoices from the last 30 days
    $invoices = $api->get('invoices', [
        'statuses' => ['unpaid'],
        'createdDateFrom' => date('Y-m-d', strtotime('-30 days'))
    ]);

    $verifiedCount = 0;

    foreach ($invoices as $invoice) {
        // Look for potential Paystack references in invoice notes or generate expected reference
        $invoiceId = $invoice['id'];
        
        // Check for transactions with our reference pattern
        // This is a simplified approach - in production you might store references in a database
        $possibleReferences = [];
        
        // Generate possible references for the last 7 days
        for ($i = 0; $i < 7; $i++) {
            $timestamp = strtotime("-{$i} days");
            $dayStart = strtotime(date('Y-m-d 00:00:00', $timestamp));
            $dayEnd = strtotime(date('Y-m-d 23:59:59', $timestamp));
            
            // Check common hour intervals
            for ($hour = 0; $hour < 24; $hour++) {
                $hourTimestamp = $dayStart + ($hour * 3600);
                $possibleReferences[] = "uisp-{$invoiceId}-{$hourTimestamp}";
            }
        }

        foreach ($possibleReferences as $reference) {
            try {
                $verification = $controller->verifyTransaction($reference);
                
                if ($verification['status'] && $verification['data']['status'] === 'success') {
                    // Found a successful payment, mark invoice as paid
                    $controller->markInvoicePaid($invoiceId, $verification['data']);
                    $verifiedCount++;
                    
                    $logger->info("Cron verified payment for invoice {$invoiceId}", [
                        'reference' => $reference
                    ]);
                    
                    break; // Move to next invoice
                }
            } catch (\Exception $e) {
                // Transaction not found or other error, continue checking
                continue;
            }
        }
    }

    $logger->info("Cron verification completed", [
        'invoices_checked' => count($invoices),
        'payments_verified' => $verifiedCount
    ]);

} catch (\Exception $e) {
    $logger->error('Cron verification failed: ' . $e->getMessage());
} 