<?php
/**
 * ApexSMM CoinPayments Gateway Adapter
 * Real Crypto Payments with HMAC-SHA512 IPN verification.
 */

require_once __DIR__ . '/../payment-manager.php';
require_once __DIR__ . '/../../includes/logger.php';

class CoinPaymentsGateway implements PaymentGatewayInterface
{
    public function createPayment(array $payment, array $config): array
    {
        $publicKey = $config['public_key'] ?? '';
        $privateKey = $config['private_key'] ?? '';

        if (empty($publicKey) || empty($privateKey)) {
            throw new \Exception('CoinPayments API keys are not configured.');
        }

        $postData = [
            'version'       => '1',
            'cmd'           => 'create_transaction',
            'amount'        => $payment['net_amount'],
            'currency1'     => 'USD',
            'currency2'     => 'USDT.TRC20',
            'buyer_email'   => $payment['user_email'] ?? 'customer@apexsmm.com',
            'item_name'     => 'ApexSMM Deposit #' . $payment['transaction_id'],
            'invoice'       => $payment['transaction_id'],
            'ipn_url'       => url('api/payments.php?gateway=coinpayments'),
            'format'        => 'json',
            'key'           => $publicKey,
        ];

        $postString = http_build_query($postData, '', '&');
        $hmac = hash_hmac('sha512', $postString, $privateKey);

        $ch = curl_init('https://www.coinpayments.net/api.php');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => $postString,
            CURLOPT_HTTPHEADER     => ["HMAC: {$hmac}"],
        ]);

        $res = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($res, true);
        if (($data['error'] ?? 'ok') !== 'ok') {
            throw new \Exception('CoinPayments error: ' . ($data['error'] ?? 'Unknown'));
        }

        return [
            'redirect_url' => $data['result']['status_url'] ?? '',
            'address'      => $data['result']['address'] ?? '',
            'amount'       => $data['result']['amount'] ?? 0,
        ];
    }

    public function verifyPayment(array $params, array $config): array
    {
        $ipnSecret = $config['ipn_secret'] ?? '';
        $merchantId = $config['merchant_id'] ?? '';

        if (!isset($_SERVER['HTTP_HMAC']) || empty($ipnSecret)) {
            return ['verified' => false, 'error' => 'Missing HMAC signature.'];
        }

        $rawBody = file_get_contents('php://input');
        $hmac = hash_hmac('sha512', $rawBody, $ipnSecret);

        if (!hash_equals($hmac, $_SERVER['HTTP_HMAC'])) {
            return ['verified' => false, 'error' => 'HMAC signature verification failed.'];
        }

        if (($params['merchant'] ?? '') !== $merchantId) {
            return ['verified' => false, 'error' => 'Merchant ID mismatch.'];
        }

        $status = (int)($params['status'] ?? 0);
        if ($status >= 100 || $status === 2) {
            return ['verified' => true, 'tx_id' => $params['invoice'] ?? ''];
        }

        return ['verified' => false, 'status' => $status];
    }
}
