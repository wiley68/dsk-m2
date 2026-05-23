<?php

declare(strict_types=1);

namespace Avalon\Dskapipayment\Model\Service;

use Avalon\Dskapipayment\Helper\Data;
use Magento\Framework\HTTP\Client\Curl;

/**
 * DSK Control Panel product API client with DB caching.
 */
class CpApi
{
    private const REQUEST_TIMEOUT = 6;

    public function __construct(
        private readonly Curl $curl,
        private readonly ApiCache $apiCache,
        private readonly Data $helper
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchProductApi(float $price, int $productId, ?string $cid = null): ?array
    {
        $cid = $cid ?? $this->getConfiguredCid();
        if ($cid === '' || $price <= 0 || $productId < 0) {
            return null;
        }

        $normalizedPrice = $this->apiCache->normalizePrice($price);
        $cacheKey = $this->apiCache->buildCacheKey(
            ApiCache::ENDPOINT_PRODUCT,
            $cid,
            $productId,
            $normalizedPrice
        );

        $cached = $this->apiCache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $endpoint = '/function/getproduct.php?cid=' . rawurlencode($cid)
            . '&price=' . rawurlencode($normalizedPrice)
            . '&product_id=' . rawurlencode((string) $productId);

        $response = $this->makeApiRequest($endpoint);
        if ($response === null) {
            return null;
        }

        $this->apiCache->set($cacheKey, $cid, $productId, $normalizedPrice, 0, $response);

        return $response;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchProductCustomApi(
        float $price,
        int $productId,
        int $installments,
        ?string $cid = null
    ): ?array {
        $cid = $cid ?? $this->getConfiguredCid();
        if ($cid === '' || $price <= 0 || $productId < 0 || $installments < 3 || $installments > 48) {
            return null;
        }

        $normalizedPrice = $this->apiCache->normalizePrice($price);
        $cacheKey = $this->apiCache->buildCacheKey(
            ApiCache::ENDPOINT_PRODUCT_CUSTOM,
            $cid,
            $productId,
            $normalizedPrice,
            $installments
        );

        $cached = $this->apiCache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $endpoint = '/function/getproductcustom.php?cid=' . rawurlencode($cid)
            . '&price=' . rawurlencode($normalizedPrice)
            . '&product_id=' . rawurlencode((string) $productId)
            . '&dskapi_vnoski=' . rawurlencode((string) $installments);

        $response = $this->makeApiRequest($endpoint);
        if ($response === null) {
            return null;
        }

        $this->apiCache->set(
            $cacheKey,
            $cid,
            $productId,
            $normalizedPrice,
            $installments,
            $response
        );

        return $response;
    }

    public function getConfiguredCid(): string
    {
        return (string) $this->helper->getConfig(
            'avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_cid'
        );
    }

    public function isModuleEnabled(): bool
    {
        return (string) $this->helper->getConfig(
            'avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_status'
        ) === '1';
    }

    /**
     * @return array<string, mixed>
     */
    public function processClearCacheRequest(string $requestCid): array
    {
        if (!$this->isModuleEnabled()) {
            return $this->buildResponse(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $storedCid = $this->getConfiguredCid();
        if ($storedCid === '' || $requestCid === '' || !hash_equals($storedCid, $requestCid)) {
            return $this->buildResponse(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $deleted = $this->apiCache->deleteByCid($storedCid);

        return $this->buildResponse(
            [
                'success' => true,
                'deleted' => $deleted,
                'cid' => $storedCid,
            ],
            200
        );
    }

    public function isRequestFromControlPanel(): bool
    {
        $allowedHost = $this->getAllowedCpHost();
        if ($allowedHost === '') {
            return false;
        }

        foreach ($this->getRequestSourceHosts() as $host) {
            if (hash_equals($allowedHost, $host)) {
                return true;
            }
        }

        return false;
    }

    public function getControlPanelOrigin(): string
    {
        return rtrim($this->helper->getDskapiLiveUrl(), '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildResponse(array $payload, int $httpStatus): array
    {
        $payload['_http_status'] = $httpStatus;

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function makeApiRequest(string $endpoint, int $timeout = self::REQUEST_TIMEOUT): ?array
    {
        $url = rtrim($this->helper->getDskapiLiveUrl(), '/') . $endpoint;

        $this->curl->setTimeout($timeout);
        $this->curl->setOptions([
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ]);
        $this->curl->get($url);

        $response = $this->curl->getBody();
        $status = (int) $this->curl->getStatus();

        if ($status < 200 || $status >= 300 || $response === null || $response === '') {
            return null;
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function getAllowedCpHost(): string
    {
        $host = parse_url($this->helper->getDskapiLiveUrl(), PHP_URL_HOST);
        if (empty($host) || !is_string($host)) {
            return '';
        }

        return strtolower($host);
    }

    /**
     * @return string[]
     */
    private function getRequestSourceHosts(): array
    {
        $sources = [];
        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            $sources[] = (string) $_SERVER['HTTP_ORIGIN'];
        }
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $sources[] = (string) $_SERVER['HTTP_REFERER'];
        }
        if (!empty($_SERVER['HTTP_X_DSKAPI_CP_ORIGIN'])) {
            $sources[] = (string) $_SERVER['HTTP_X_DSKAPI_CP_ORIGIN'];
        }

        $hosts = [];
        foreach ($sources as $value) {
            $host = parse_url($value, PHP_URL_HOST);
            if (empty($host) || !is_string($host)) {
                $host = preg_replace('/:\d+$/', '', trim($value)) ?? '';
            }
            if ($host !== '') {
                $hosts[] = strtolower($host);
            }
        }

        return array_values(array_unique($hosts));
    }
}
