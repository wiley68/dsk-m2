<?php

declare(strict_types=1);

namespace Avalon\Dskapipayment\Controller\Index;

use Avalon\Dskapipayment\Model\Service\CpApi;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Proxies getproductcustom API calls with local DB caching.
 */
class Getproductcustom implements HttpGetActionInterface
{
    public function __construct(
        private readonly JsonFactory $resultJsonFactory,
        private readonly RequestInterface $request,
        private readonly CpApi $cpApi
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $configuredCid = $this->cpApi->getConfiguredCid();
        $cid = (string) $this->request->getParam('cid', '');

        if ($configuredCid === '' || !hash_equals($configuredCid, $cid)) {
            return $result->setHttpResponseCode(403)->setData(['error' => 'invalid_cid']);
        }

        $price = (float) $this->request->getParam('price', 0);
        $productId = (int) $this->request->getParam('product_id', 0);
        $installments = (int) $this->request->getParam('dskapi_vnoski', 0);

        if ($price <= 0 || $productId < 0 || $installments < 3 || $installments > 48) {
            return $result->setHttpResponseCode(400)->setData(['error' => 'invalid_params']);
        }

        $response = $this->cpApi->fetchProductCustomApi($price, $productId, $installments, $cid);
        if ($response === null) {
            return $result->setHttpResponseCode(502)->setData(new \stdClass());
        }

        return $result->setData($response);
    }
}
