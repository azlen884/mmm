<?php
/**
 * ApexSMM PayPal Gateway Adapter
 * Standard PayPal REST API v2 Checkout integration.
 */

require_once __DIR__ . '/../payment-manager.php';
require_once __DIR__ . '/../../includes/logger.php';

class PayPalGateway implements PaymentGatewayInterface
{
    private function getAccessToken(string $clientId, string $clientSecret, string $mode): string
    {
        $baseUrl = ($mode === 'live') ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
        $ch = curl_init("{$baseUrl}/v1/oauth2/token");
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => "{$clientId}:{$clientSecret}",
            CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
        ]);
        $res = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($res, true);
        if (empty($data['access_token'])) {
            throw new \Exception('Failed to authenticate with PayPal API.');
        }
        return $data['access_token'];
    }

    public function createPayment(array $payment, array $config): array
    {
        $clientId = $config['client_id'] ?? '';
        $clientSecret = $config['client_secret'] ?? '';
        $mode = $config['mode'] ?? 'sandbox';

        if (empty($clientId) || empty($clientSecret)) {
            throw new \Exception('PayPal credentials are not configured.');
        }

        $token = $this->getAccessToken($clientId, $clientSecret, $mode);
        $baseUrl = ($mode === 'live') ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        $body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $payment['transaction_id'],
                    'description'  => 'ApexSMM Wallet Deposit',
                    'amount'       => [
                        'currency_code' => $payment['currency'] ?? 'USD',
                        'value'         => number_format((float)$payment['net_amount'], 2, '.', ''),
                    ],
                ]
            ],
            'application_context' => [
                'return_url' => url("user/add-funds.php?gateway=paypal&status=success&tx=" . urlencode($payment['transaction_id'])),
                'cancel_url' => url("user/add-funds.php?gateway=paypal&status=cancelled&tx=" . urlencode($payment['transaction_id'])),
            ]
        ];

        $ch = curl_init("{$baseUrl}/v2/checkout/orders");
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$token}",
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($body),
        ]);

        $res = curl_exec($ch);
        curl_close($ch);

        $order = json_decode($res, true);
        $approveUrl = '';
        if (!empty($order['links'])) {
            foreach ($order['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approveUrl = $link['href'];
                    break;
                }
            }
        }

        if (empty($approveUrl)) {
            throw new \Exception($order['message'] ?? 'Could not create PayPal checkout order.');
        }

        return [
            'redirect_url' => $approveUrl,
            'order_id'     => $order['id'],
        ];
    }

    public function verifyPayment(array $params, array $config): array
    {
        $clientId = $config['client_id'] ?? '';
        $clientSecret = $config['client_secret'] ?? '';
        $mode = $config['mode'] ?? 'sandbox';
        $paypalToken = $params['token'] ?? ''; // order id

        if (empty($paypalToken)) {
            return ['verified' => false, 'error' => 'Missing PayPal order token.'];
        }

        $token = $this->getAccessToken($clientId, $clientSecret, $mode);
        $baseUrl = ($mode === 'live') ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        $ch = curl_init("{$baseUrl}/v2/checkout/orders/{$paypalToken}/capture");
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$token}",
                'Content-Type: application/json',
            ],
        ]);

        $res = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($res, true);
        if (($data['status'] ?? '') === 'COMPLETED') {
            return ['verified' => true];
        }

        return ['verified' => false, 'error' => $data['message'] ?? 'Capture could not be completed.'];
    }
}
