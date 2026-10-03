<?php
/**
 * ApexSMM Payment Manager & Gateway Registry
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/logger.php';

interface PaymentGatewayInterface
{
    public function createPayment(array $payment, array $config): array;
    public function verifyPayment(array $params, array $config): array;
}

class PaymentManager
{
    public static function getActiveGateways(): array
    {
        return Database::fetchAll(
            "SELECT id, code, name, min_amount, max_amount, fee_percentage, fee_fixed, instructions 
             FROM payment_gateways 
             WHERE status = 'active' 
             ORDER BY id ASC"
        );
    }

    public static function getGateway(string $code): ?array
    {
        return Database::fetchOne("SELECT * FROM payment_gateways WHERE code = ?", [$code]);
    }

    public static function getHandler(string $code): ?PaymentGatewayInterface
    {
        $file = __DIR__ . '/gateways/' . ucfirst($code) . 'Gateway.php';
        if (file_exists($file)) {
            require_once $file;
            $class = ucfirst($code) . 'Gateway';
            if (class_exists($class)) {
                return new $class();
            }
        }
        return null;
    }
}
