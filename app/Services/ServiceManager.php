<?php
/**
 * ApexSMM Service & Category Management Engine
 */

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/logger.php';

class ServiceManager
{
    public static function getActiveCategories(): array
    {
        return Database::fetchAll(
            "SELECT c.*, COUNT(s.id) as service_count 
             FROM categories c 
             LEFT JOIN services s ON c.id = s.category_id AND s.status = 'active' 
             WHERE c.status = 'active' 
             GROUP BY c.id 
             ORDER BY c.sort_order ASC, c.name ASC"
        );
    }

    public static function getServices(?int $categoryId = null, string $search = '', bool $onlyActive = true): array
    {
        $params = [];
        $where = [];

        if ($onlyActive) {
            $where[] = "s.status = 'active'";
        }

        if ($categoryId) {
            $where[] = "s.category_id = ?";
            $params[] = $categoryId;
        }

        if ($search !== '') {
            $where[] = "(s.name LIKE ? OR s.description LIKE ? OR c.name LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT s.*, c.name as category_name, p.name as provider_name 
                FROM services s 
                LEFT JOIN categories c ON s.category_id = c.id 
                LEFT JOIN providers p ON s.provider_id = p.id 
                {$whereClause} 
                ORDER BY c.sort_order ASC, c.name ASC, s.id ASC";

        return Database::fetchAll($sql, $params);
    }

    public static function getService(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT s.*, c.name as category_name, p.name as provider_name 
             FROM services s 
             LEFT JOIN categories c ON s.category_id = c.id 
             LEFT JOIN providers p ON s.provider_id = p.id 
             WHERE s.id = ?",
            [$id]
        );
    }
}
