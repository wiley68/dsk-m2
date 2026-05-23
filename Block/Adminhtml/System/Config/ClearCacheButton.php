<?php

declare(strict_types=1);

namespace Avalon\Dskapipayment\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * System config button to clear DSK installment API cache.
 */
class ClearCacheButton extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Avalon_Dskapipayment::system/config/clear_cache_button.phtml';

    /**
     * @inheritdoc
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->toHtml();
    }

    /**
     * Admin URL for cache purge action.
     */
    public function getClearCacheUrl(): string
    {
        return $this->getUrl('dskapipayment/system/clearcache');
    }
}
