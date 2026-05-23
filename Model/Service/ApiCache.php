<?php

declare(strict_types=1);

namespace Avalon\Dskapipayment\Model\Service;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * DB cache for DSK Control Panel product API responses.
 */
class ApiCache
{
    public const TABLE = 'dskapipayment_api_cache';

    public const TTL_SECONDS = 900;

    public const ENDPOINT_PRODUCT = 'getproduct';

    public const ENDPOINT_PRODUCT_CUSTOM = 'getproductcustom';

    private AdapterInterface $connection;

    private string $tableName;

    public function __construct(
        ResourceConnection $resource,
        private readonly DateTime $dateTime
    ) {
        $this->connection = $resource->getConnection();
        $this->tableName = $resource->getTableName(self::TABLE);
    }

    public function buildCacheKey(
        string $endpointType,
        string $cid,
        int $productId,
        string $price,
        int $installments = 0
    ): string {
        return hash('sha256', $endpointType . '|' . $cid . '|' . $productId . '|' . $price . '|' . $installments);
    }

    public function normalizePrice(float $price): string
    {
        return number_format($price, 2, '.', '');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $cacheKey): ?array
    {
        $select = $this->connection->select()
            ->from($this->tableName, ['response_json'])
            ->where('cache_key = ?', $cacheKey)
            ->where('expires_at > ?', $this->dateTime->gmtDate());

        $json = $this->connection->fetchOne($select);
        if (!$json) {
            return null;
        }

        $decoded = json_decode((string) $json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $response
     */
    public function set(
        string $cacheKey,
        string $cid,
        int $productId,
        string $price,
        int $installments,
        array $response
    ): bool {
        $this->deleteExpired();

        $responseJson = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($responseJson === false) {
            return false;
        }

        $now = time();
        $createdAt = $this->dateTime->gmtDate(null, $now);
        $expiresAt = $this->dateTime->gmtDate(null, $now + self::TTL_SECONDS);

        $this->connection->insertOnDuplicate(
            $this->tableName,
            [
                'cache_key' => $cacheKey,
                'cid' => $cid,
                'product_id' => $productId,
                'price' => $price,
                'installments' => $installments,
                'response_json' => $responseJson,
                'created_at' => $createdAt,
                'expires_at' => $expiresAt,
            ],
            [
                'cid',
                'product_id',
                'price',
                'installments',
                'response_json',
                'created_at',
                'expires_at',
            ]
        );

        return true;
    }

    public function deleteExpired(): void
    {
        $this->connection->delete(
            $this->tableName,
            ['expires_at < ?' => $this->dateTime->gmtDate()]
        );
    }

    public function clearAll(): int
    {
        $count = (int) $this->connection->fetchOne(
            $this->connection->select()->from($this->tableName, [new Expression('COUNT(*)')])
        );

        if ($count > 0) {
            $this->connection->delete($this->tableName);
        }

        return $count;
    }

    public function deleteByCid(string $cid): int
    {
        if ($cid === '') {
            return 0;
        }

        $count = (int) $this->connection->fetchOne(
            $this->connection->select()
                ->from($this->tableName, [new Expression('COUNT(*)')])
                ->where('cid = ?', $cid)
        );

        if ($count > 0) {
            $this->connection->delete($this->tableName, ['cid = ?' => $cid]);
        }

        return $count;
    }
}
