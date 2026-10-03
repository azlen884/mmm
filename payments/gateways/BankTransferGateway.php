<?php
/**
 * ApexSMM Bank Transfer & Manual Payment Gateway
 */

require_once __DIR__ . '/../payment-manager.php';
require_once __DIR__ . '/../../includes/logger.php';

class BankTransferGateway implements PaymentGatewayInterface
{
    public function createPayment(array $payment, array $config): array
    {
        return [
            'redirect_url' => url("user/add-funds.php?action=manual_receipt&tx=" . urlencode($payment['transaction_id'])),
            'instructions' => $config['instructions'] ?? 'Please wire funds to our bank account and submit receipt proof.',
        ];
    }

    public function verifyPayment(array $params, array $config): array
    {
        // Verified by administrator manually from admin/payments.php
        return ['verified' => false, 'error' => 'Bank transfers require manual admin approval.'];
    }
}
