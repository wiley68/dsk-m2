<?php

declare(strict_types=1);

namespace Avalon\Dskapipayment\Controller\Index;

use Avalon\Dskapipayment\Model\Service\CpApi;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;

/**
 * Control Panel endpoint: purge installment calculation cache for this store.
 */
class Cpclearcache implements ActionInterface
{
    public const PARAM_CID = 'cid';

    public function __construct(
        private readonly JsonFactory $resultJsonFactory,
        private readonly ResultFactory $resultFactory,
        private readonly HttpRequest $request,
        private readonly CpApi $cpApi
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->request->isOptions()) {
            return $this->createOptionsResponse();
        }

        if (!$this->request->isPost()) {
            $result = $this->resultJsonFactory->create();
            $this->applyCorsHeaders($result);

            return $result->setHttpResponseCode(405)->setData([
                'success' => false,
                'message' => 'Method not allowed',
            ]);
        }

        $result = $this->resultJsonFactory->create();
        $this->applyCorsHeaders($result);

        if (!$this->cpApi->isRequestFromControlPanel()) {
            return $result->setHttpResponseCode(403)->setData([
                'success' => false,
                'message' => 'Forbidden',
            ]);
        }

        $requestCid = (string) $this->request->getParam(self::PARAM_CID, '');
        $payload = $this->cpApi->processClearCacheRequest($requestCid);
        $httpStatus = (int) ($payload['_http_status'] ?? 200);
        unset($payload['_http_status']);

        return $result->setHttpResponseCode($httpStatus)->setData($payload);
    }

    /**
     * OPTIONS preflight is not routed to HttpPostActionInterface; expose via raw result when needed.
     */
    private function createOptionsResponse()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHttpResponseCode(200);
        $result->setHeader('Access-Control-Allow-Origin', $this->cpApi->getControlPanelOrigin(), true);
        $result->setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS', true);
        $result->setHeader('Access-Control-Allow-Headers', 'Content-Type, X-Dskapi-Cp-Origin', true);

        return $result;
    }

    private function applyCorsHeaders(\Magento\Framework\Controller\Result\Json $result): void
    {
        $result->setHeader('Access-Control-Allow-Origin', $this->cpApi->getControlPanelOrigin(), true);
        $result->setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS', true);
        $result->setHeader('Access-Control-Allow-Headers', 'Content-Type, X-Dskapi-Cp-Origin', true);
    }
}
