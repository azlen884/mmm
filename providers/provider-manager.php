<?php
/**
 * ApexSMM Provider Manager
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/adapters/StandardAdapter.php';

class ProviderManager
{
    public static function getAdapter(int $providerId): StandardAdapter
    {
        $provider = Database::fetchOne("SELECT * FROM providers WHERE id = ?", [$providerId]);
        if (!$provider) {
            throw new \Exception("Provider #{$providerId} does not exist.");
        }

        if (empty($provider['api_url']) || empty($provider['api_key'])) {
            throw new \Exception("Provider #{$providerId} configuration is incomplete.");
        }

        return new StandardAdapter($provider['api_url'], $provider['api_key']);
    }

    public static function testConnection(int $providerId): array
    {
        $adapter = self::getAdapter($providerId);
        $res = $adapter->getBalance();

        $balance = (float)($res['balance'] ?? 0);
        $currency = (string)($res['currency'] ?? 'USD');

        Database::execute(
            "UPDATE providers SET balance = ?, currency = ?, last_sync_at = NOW() WHERE id = ?",
            [$balance, $currency, $providerId]
        );

        return [
            'success'  => true,
            'balance'  => $balance,
            'currency' => $currency,
        ];
    }
}
