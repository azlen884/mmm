<?php
/**
 * ApexSMM Standard SMM API Adapter
 * Communicates with any standard SMM provider API protocol.
 */

require_once __DIR__ . '/../../includes/logger.php';

class StandardAdapter
{
    private string $apiUrl;
    private string $apiKey;

    public function __construct(string $apiUrl, string $apiKey)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
    }

    private function post(array $postData): array
    {
        $postData['key'] = $this->apiKey;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->apiUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'ApexSMM-Provider-Client/1.0',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Logger::provider("cURL error connecting to {$this->apiUrl}: " . $curlError);
            throw new \Exception("Connection to provider failed: " . $curlError);
        }

        if ($httpCode >= 400) {
            Logger::provider("HTTP error {$httpCode} from {$this->apiUrl}", ['response' => substr($response, 0, 500)]);
            throw new \Exception("Provider server responded with HTTP {$httpCode}");
        }

        $decoded = json_decode($response, true);
        if ($decoded === null && !empty($response)) {
            Logger::provider("Invalid JSON from {$this->apiUrl}", ['raw' => substr($response, 0, 500)]);
            throw new \Exception("Invalid JSON response received from provider.");
        }

        if (isset($decoded['error'])) {
            throw new \Exception("Provider API Error: " . $decoded['error']);
        }

        return $decoded ?: [];
    }

    public function getBalance(): array
    {
        return $this->post(['action' => 'balance']);
    }

    public function getServices(): array
    {
        return $this->post(['action' => 'services']);
    }

    public function addOrder(string $providerServiceId, string $link, int $quantity, array $extra = []): array
    {
        $params = array_merge([
            'action'   => 'add',
            'service'  => $providerServiceId,
            'link'     => $link,
            'quantity' => $quantity,
        ], $extra);

        return $this->post($params);
    }

    public function getStatus(string $providerOrderId): array
    {
        return $this->post([
            'action' => 'status',
            'order'  => $providerOrderId,
        ]);
    }

    public function getMultiStatus(array $providerOrderIds): array
    {
        return $this->post([
            'action' => 'status',
            'orders' => implode(',', $providerOrderIds),
        ]);
    }
}
