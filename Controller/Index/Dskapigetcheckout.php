<?php

/**
 * Dskapigetcheckout File Doc Comment
 *
 * PHP version 8
 *
 * @category Dskapigetcheckout
 * @package  Dskapigetcheckout
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */

namespace Avalon\Dskapipayment\Controller\Index;

use \Avalon\Dskapipayment\Helper\Data;

/**
 * Dskapigetcheckout Class Doc Comment
 *
 * Dskapigetcheckout Class
 *
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */
class Dskapigetcheckout implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory $_resultJsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Checkout\Model\Session $checkoutSession
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $_quoteRepository;

    /**
     * @var \Avalon\Dskapipayment\Model\DskapiApi
     */
    protected $_dskapiApi;

    /**
     * Dskapigetcheckout constructor Doc Comment
     *
     * Dskapigetcheckout Class constructor
     *
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Avalon\Dskapipayment\Model\DskapiApi $dskapiApi
     */
    public function __construct(
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Avalon\Dskapipayment\Model\DskapiApi $dskapiApi
    ) {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_storeManager = $storeManager;
        $this->_checkoutSession = $checkoutSession;
        $this->_quoteRepository = $quoteRepository;
        $this->_dskapiApi = $dskapiApi;
    }

    /**
     * Dskapigetcheckout getGrandTotal Doc Comment
     *
     * Dskapigetcheckout getGrandTotal function
     *
     * @return mixed
     */
    public function getGrandTotal()
    {
        $quote = $this->_checkoutSession->getQuote();
        /** @var \Magento\Quote\Model\Quote $quote */
        $grandTotal = $quote->getGrandTotal();
        return $grandTotal;
    }

    /**
     * Dskapigetcheckout getCurrentCartId Doc Comment
     *
     * Dskapigetcheckout getCurrentCartId function
     *
     * @return mixed
     */
    public function getCurrentCartId()
    {
        $quoteId = $this->_checkoutSession->getQuote()->getId();
        return $quoteId;
    }

    /**
     * Dskapigetcheckout execute Doc Comment
     *
     * Dskapigetcheckout execute function
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $json = [];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->create(Data::class);

        if ($helper->getConfig('avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_cid')) {
            $dskapi_cid = $helper->getConfig('avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_cid');
        } else {
            $dskapi_cid = "";
        }
        if ($helper->getConfig('avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_status')) {
            $dskapi_status = $helper->getConfig('avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_status');
        } else {
            $dskapi_status = "";
        }

        $dskapiUrl = $helper->getDskapiLiveUrl() . '/function/getminmax.php?cid=' . $dskapi_cid;
        $response = $this->_dskapiApi->fetchData($dskapiUrl);
        $paramsdskapi = json_decode($response, true);

        $dskapi_price = $this->getGrandTotal();
        $store = $this->_storeManager->getStore();
        /** @var \Magento\Store\Model\Store $store */
        $dskapi_currency_code = $store->getCurrentCurrencyCode();
        $dskapi_eur = (int)$paramsdskapi['dsk_eur'];
        switch ($dskapi_eur) {
            case 0:
                break;
            case 1:
                if ($dskapi_currency_code == "EUR") {
                    $dskapi_price = $dskapi_price * 1.95583;
                }
                break;
            case 2:
                if ($dskapi_currency_code == "BGN") {
                    $dskapi_price = $dskapi_price / 1.95583;
                }
                break;
        }
        $dskapi_minstojnost = (float)$paramsdskapi['dsk_minstojnost'];
        $dskapi_maxstojnost = (float)$paramsdskapi['dsk_maxstojnost'];
        $dskapi_status_cp = $paramsdskapi['dsk_status'];

        $dskapi_purcent = (float)$paramsdskapi['dsk_purcent'];
        $dskapi_vnoski_default = (int)$paramsdskapi['dsk_vnoski_default'];
        if (($dskapi_purcent == 0) && ($dskapi_vnoski_default <= 6)) {
            $dskapi_minstojnost = 100;
        }

        if (
            ($dskapi_status != "1") ||
            ($dskapi_status_cp == 0) ||
            ($dskapi_price < $dskapi_minstojnost) ||
            ($dskapi_price > $dskapi_maxstojnost) ||
            ($dskapi_currency_code != 'EUR' && $dskapi_currency_code != 'BGN')
        ) {
            $json['dskapi_status'] = "No";
        } else {
            $json['dskapi_status'] = "Yes";
        }

        // Add product_id for popup functionality
        $quote = $this->_checkoutSession->getQuote();
        /** @var \Magento\Quote\Model\Quote $quote */
        $cartItems = $quote->getAllItems();
        $productId = 0;
        if (!empty($cartItems)) {
            $uniqueIds = [];
            foreach ($cartItems as $item) {
                $itemProductId = (int)($item->getProductId() ?? 0);
                if ($itemProductId > 0) {
                    $uniqueIds[$itemProductId] = true;
                }
                if (count($uniqueIds) > 1) {
                    break;
                }
            }
            if (count($uniqueIds) === 1) {
                reset($uniqueIds);
                $productId = (int)key($uniqueIds);
            }
        }
        $json['dskapi_product_id'] = $productId;

        // Add configuration values for popup functionality
        $json['dskapi_cid'] = $dskapi_cid;
        $json['dskapi_live_url'] = $helper->getDskapiLiveUrl();
        $json['dskapi_eur'] = $dskapi_eur;
        $json['currency_code'] = $dskapi_currency_code;
        $json['dskapi_module_version'] = $helper->getModuleVersion();

        $result = $this->_resultJsonFactory->create();
        $result->setData($json);
        return $result;
    }
}
