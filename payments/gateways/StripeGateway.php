<?php
/**
 * ApexSMM Stripe Gateway Adapter
 * Handles checkout sessions and webhook verification.
 */

require_once __DIR__ . '/../payment-manager.php';
require_once __DIR__ . '/../../includes/logger.php';

class StripeGateway implements PaymentGatewayInterface
{
    public function createPayment(array $payment, array $config): array
    {
        $secretKey = $config['secret_key'] ?? '';
        if (empty($secretKey)) {
            throw new \Exception('Stripe Secret Key is not configured.');
        }

        $amountInCents = (int)round((float)$payment['net_amount'] * 100);
        $successUrl = url("user/add-funds.php?status=success&tx=" . urlencode($payment['transaction_id']));
        $cancelUrl  = url("user/add-funds.php?status=cancelled&tx=" . urlencode($payment['transaction_id']));

        // Call Stripe Checkout Sessions API via standard cURL
        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $secretKey . ':',
            CURLOPT_POSTFIELDS     => http_build_query([
                'payment_method_types' => ['card'],
                'line_items'           => [
                    [
                        'price_data' => [
                            'currency'     => strtolower($payment['currency'] ?? 'usd'),
                            'unit_amount'  => $amountInCents,
                            'product_data' => [
                                'name' => 'Account Wallet Funds (ApexSMM)',
                            ],
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode'        => 'payment',
                'client_reference_id' => $payment['transaction_id'],
                'success_url' => $successUrl,
                'cancel_url'  => $cancelUrl,
            ]),
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \Exception('Stripe API error: ' . $err);
        }

        $session = json_decode($response, true);
        if (empty($session['url'])) {
            $msg = $session['error']['message'] ?? 'Could not create Stripe Checkout session.';
            throw new \Exception($msg);
        }

        return [
            'redirect_url' => $session['url'],
            'session_id'   => $session['id'],
        ];
    }

    public function verifyPayment(array $params, array $config): array
    {
        $secretKey = $config['secret_key'] ?? '';
        $sessionId = $params['session_id'] ?? '';

        if (empty($sessionId) || empty($secretKey)) {
            return ['verified' => false, 'error' => 'Missing session verification details.'];
        }

        $ch = curl_init("https://api.stripe.com/v1/checkout/sessions/" . urlencode($sessionId));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $secretKey . ':',
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $session = json_decode($response, true);
        if (($session['payment_status'] ?? '') === 'paid') {
            return [
                'verified' => true,
                'amount'   => ($session['amount_total'] ?? 0) / 100,
                'tx_id'    => $session['client_reference_id'] ?? '',
            ];
        }

        return ['verified' => false, 'error' => 'Payment has not been completed.'];
    }
}
