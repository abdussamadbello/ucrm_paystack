<?php

/**
 * Paystack Payment Gateway Plugin for UISP CRM
 * Main entry point - required by UISP plugin system
 */

require_once __DIR__ . '/vendor/autoload.php';

// This file serves as the main entry point for the plugin
// The actual functionality is handled by the files defined in manifest.json:
// - public/index.php for the payment interface
// - backend/Webhook.php for webhook handling  
// - backend/CronVerify.php for background reconciliation

// Plugin is ready for use
return true; 