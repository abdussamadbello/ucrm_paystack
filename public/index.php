<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PaystackGateway\Controller;
use Ubnt\UcrmPluginSdk\Service\UcrmApi;
use Ubnt\UcrmPluginSdk\Service\PluginConfigManager;

$api = UcrmApi::create();
$config = PluginConfigManager::create();
$controller = new Controller();

$invoiceId = $_POST['invoice'] ?? $_GET['invoice'] ?? null;
$error = null;
$paymentData = null;

if ($_POST['action'] ?? null === 'pay' && $invoiceId) {
    try {
        $paymentData = $controller->initTransaction((int) $invoiceId);
    } catch (\Exception $e) {
        $error = $e->getMessage();
    }
}

// Get unpaid invoices for display
$invoices = $api->get('invoices', ['statuses' => ['unpaid'], 'limit' => 50]);

$testMode = $config->get('testMode', false);
$publicKey = $testMode ? 'pk_test_...' : $config->get('pkLive');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paystack Payment Gateway</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .invoice { border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px; }
        .btn { background: #0066cc; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #0052a3; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .test-mode { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
    <script src="https://js.paystack.co/v1/inline.js"></script>
</head>
<body>
    <h1>Paystack Payment Gateway</h1>
    
    <?php if ($testMode): ?>
        <div class="test-mode">
            <strong>Test Mode Active:</strong> This plugin is running in test mode. No real payments will be processed.
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($paymentData): ?>
        <div class="success">
            Payment initialized successfully. Redirecting to Paystack...
        </div>
        <script>
            window.location.href = '<?= $paymentData['authorization_url'] ?>';
        </script>
    <?php endif; ?>

    <h2>Unpaid Invoices</h2>
    
    <?php if (empty($invoices)): ?>
        <p>No unpaid invoices found.</p>
    <?php else: ?>
        <?php foreach ($invoices as $invoice): ?>
            <div class="invoice">
                <h3>Invoice #<?= $invoice['number'] ?></h3>
                <p><strong>Amount:</strong> ₦<?= number_format($invoice['total'], 2) ?></p>
                <p><strong>Due Date:</strong> <?= date('M j, Y', strtotime($invoice['dueDate'])) ?></p>
                <p><strong>Client:</strong> <?= htmlspecialchars($invoice['clientFirstName'] . ' ' . $invoice['clientLastName']) ?></p>
                
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="pay">
                    <input type="hidden" name="invoice" value="<?= $invoice['id'] ?>">
                    <button type="submit" class="btn">Pay with Paystack</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2>Configuration</h2>
    <p>
        <strong>Webhook URL:</strong> 
        <code><?= $api->getAppUrl() ?>/plugins/paystack-gateway/hook/paystack</code>
    </p>
    <p>Configure this URL in your Paystack dashboard under Settings → Webhooks.</p>
    
    <h2>Paystack IP Allowlist</h2>
    <p>Ensure your firewall allows these Paystack IP ranges:</p>
    <ul>
        <li>52.31.139.75/24</li>
        <li>52.49.173.169/32</li>
    </ul>
</body>
</html> 