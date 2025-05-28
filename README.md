# Paystack Payment Gateway for UISP CRM

A complete open-source plugin that enables Nigerian ISPs to accept Paystack payments (card, bank transfer, USSD) directly within UISP CRM.

## Features

- **Multiple Payment Methods**: Card, bank transfer, USSD via Paystack
- **Secure Processing**: No card data stored locally, Paystack-hosted checkout
- **Automatic Reconciliation**: Webhook notifications + cron job verification
- **Test Mode Support**: Safe testing with Paystack test keys
- **Invoice Integration**: Seamless UISP invoice payment workflow

## Installation

1. **Download & Upload**
   ```bash
   # Build the plugin package
   composer install
   composer run pack
   ```
   
2. **Install in UISP**
   - Go to *Settings → System → Plugins*
   - Click *Upload* and select `paystack-gateway.zip`
   - Enable the plugin

## Configuration

### 1. Paystack Setup

1. **Get API Keys**
   - Login to [Paystack Dashboard](https://dashboard.paystack.com)
   - Go to *Settings → API Keys & Webhooks*
   - Copy your Public and Secret keys

2. **Configure Webhook**
   - In Paystack Dashboard: *Settings → API Keys & Webhooks*
   - Add webhook URL: `https://your-uisp-domain.com/plugins/paystack-gateway/hook/paystack`
   - Select events: `charge.success`

### 2. Plugin Configuration

1. **Navigate to Plugin Settings**
   - *Settings → System → Plugins → Paystack Payment Gateway*

2. **Enter Credentials**
   - **Paystack Public Key (Live)**: Your live public key (pk_live_...)
   - **Paystack Secret Key (Live)**: Your live secret key (sk_live_...)
   - **Test Mode**: Check for testing, uncheck for production

### 3. Firewall Configuration

Allow Paystack webhook IPs in your firewall:
- `52.31.139.75/24`
- `52.49.173.169/32`

*Note: IP ranges may change. Check [Paystack documentation](https://paystack.com/docs/payments/webhooks/#ip-whitelisting) for updates.*

## Usage

### For Administrators

1. **View Payment Interface**
   - Navigate to *Paystack* in the main menu
   - View all unpaid invoices
   - Monitor payment status

2. **Manual Payment Processing**
   - Click "Pay with Paystack" next to any unpaid invoice
   - System redirects to Paystack checkout
   - Payment automatically recorded upon success

### For Clients

1. **Invoice Payment**
   - Receive invoice notification
   - Click payment link or visit payment portal
   - Complete payment via Paystack (card/bank/USSD)
   - Invoice automatically marked as paid

## Technical Details

### Payment Flow

1. **Initialization**
   - Client clicks "Pay with Paystack"
   - Plugin creates Paystack transaction with reference `uisp-{invoiceId}-{timestamp}`
   - Client redirected to Paystack checkout

2. **Payment Processing**
   - Client completes payment on Paystack
   - Paystack sends webhook notification
   - Plugin verifies signature and transaction
   - Invoice marked as paid in UISP

3. **Reconciliation**
   - Cron job runs every 5 minutes
   - Verifies unpaid invoices against Paystack
   - Catches any missed webhook notifications

### Security Features

- **Webhook Signature Verification**: HMAC-SHA512 validation
- **No Card Data Storage**: All payment data handled by Paystack
- **API Key Protection**: Keys stored in encrypted plugin configuration
- **Transaction Verification**: Double-check via Paystack API

## Testing

### Test Mode Setup

1. **Enable Test Mode**
   - Check "Test Mode" in plugin configuration
   - Use Paystack test keys (pk_test_... / sk_test_...)

2. **Test Payment**
   - Use test card: `4084084084084081`
   - Any future expiry date and CVV
   - Complete test transaction

3. **Verify Integration**
   - Check webhook receives notifications
   - Confirm invoice marked as paid
   - Review plugin logs

### Test Cards

| Card Number | Description |
|-------------|-------------|
| 4084084084084081 | Successful transaction |
| 4084084084084081 | Declined transaction |

*See [Paystack test cards](https://paystack.com/docs/payments/test-payments/#test-cards) for complete list.*

## Troubleshooting

### Common Issues

1. **Webhook Not Receiving Notifications**
   - Verify webhook URL in Paystack dashboard
   - Check firewall allows Paystack IPs
   - Review UISP error logs

2. **Payment Not Recorded**
   - Check webhook signature verification
   - Verify API keys are correct
   - Review plugin logs in UISP

3. **Test Mode Issues**
   - Ensure using test API keys
   - Use only test card numbers
   - Check test mode is enabled

### Log Files

- **Plugin Logs**: *Settings → System → Logs → Plugins*
- **Webhook Logs**: Check HTTP server access logs
- **Paystack Logs**: Dashboard → Developers → Logs

## Upgrade Notes

### Version Compatibility

- **UISP CRM**: Requires v1.4.0 or higher
- **PHP**: Requires PHP 8.1 or higher
- **Dependencies**: Auto-managed via Composer

### Upgrade Process

1. **Backup Configuration**
   - Export plugin settings
   - Note webhook URLs

2. **Install New Version**
   - Upload new plugin package
   - Reconfigure if needed

3. **Verify Operation**
   - Test payment flow
   - Check webhook functionality

## Support

### Documentation

- [Paystack API Documentation](https://paystack.com/docs)
- [UISP Plugin Development](https://github.com/Ubiquiti-App/UCRM-plugins)

### Community

- **Issues**: Report bugs via GitHub issues
- **Discussions**: Community forums and Discord
- **Contributions**: Pull requests welcome

## License

This plugin is open-source software licensed under the MIT License.

## Changelog

### v1.0.0
- Initial release
- Card, bank transfer, USSD payment support
- Webhook integration
- Cron reconciliation
- Test mode support 