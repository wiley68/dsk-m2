<?php

declare(strict_types=1);

namespace Avalon\Dskapipayment\Controller\Adminhtml\System;

use Avalon\Dskapipayment\Model\Service\ApiCache;
use Avalon\Dskapipayment\Model\Service\CpApi;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Clears cached DSK installment calculation responses from admin configuration.
 */
class ClearCache extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Avalon_Dskapipayment::configure';

    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly ApiCache $apiCache,
        private readonly CpApi $cpApi
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $storedCid = $this->cpApi->getConfiguredCid();
        $deleted = $storedCid !== ''
            ? $this->apiCache->deleteByCid($storedCid)
            : $this->apiCache->clearAll();

        return $result->setData([
            'success' => true,
            'deleted' => $deleted,
            'message' => (string) __(
                'Кешът е изчистен. Премахнати са %1 записа.',
                $deleted
            ),
        ]);
    }
}
