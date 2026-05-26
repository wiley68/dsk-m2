<?php

/**
 * CartButtons File Doc Comment
 *
 * PHP version 8
 *
 * @category CartButtons
 * @package  CartButtons
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */

namespace Avalon\Dskapipayment\Block;

/**
 * CartButtons Class Doc Comment
 *
 * CartButtons Class
 *
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */
class CartButtons extends \Magento\Framework\View\Element\Template
{
    use PopupImageUrlsTrait;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $_curl;

    /**
     * @var \stdClass|null
     */
    protected $_paramsdskapi;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Avalon\Dskapipayment\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Avalon\Dskapipayment\Model\Service\CpApi
     */
    protected $_cpApi;

    /**
     * @var int
     */
    protected $_dskapi_eur;

    /**
     * CartButtons constructor Doc Comment
     *
     * CartButtons Class constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Avalon\Dskapipayment\Helper\Data $helper
     * @param \Avalon\Dskapipayment\Model\Service\CpApi $cpApi
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Avalon\Dskapipayment\Helper\Data $helper,
        \Avalon\Dskapipayment\Model\Service\CpApi $cpApi,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_curl = $curl;
        $this->_storeManager = $storeManager;
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
        $this->_cpApi = $cpApi;
        $this->_dskapi_eur = $this->retrieveEur();
        $this->_paramsdskapi = $this->retrieveParamsDskapi();
    }

    /**
     * CartButtons getQuote Doc Comment
     *
     * CartButtons getQuote function,
     * return quote info
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->_checkoutSession->getQuote();
        return $quote;
    }

    /**
     * Resolves cart product ID based on cart items.
     * Returns product ID if all items are the same product, otherwise returns 0.
     *
     * @return int
     */
    protected function resolveCartProductId(): int
    {
        $quote = $this->getQuote();
        if (!$quote) {
            return 0;
        }

        $cartItems = $quote->getAllItems();
        if (empty($cartItems)) {
            return 0;
        }

        $uniqueIds = [];
        foreach ($cartItems as $item) {
            $productId = (int)($item->getProductId() ?? 0);
            if ($productId > 0) {
                $uniqueIds[$productId] = true;
            }
            if (count($uniqueIds) > 1) {
                return 0;
            }
        }

        reset($uniqueIds);
        $firstKey = key($uniqueIds);

        return (int)($firstKey ?? 0);
    }

    /**
     * CartButtons retrieveParamsDskapi Doc Comment
     *
     * CartButtons retrieveParamsDskapi function,
     * return params info
     *
     * @return \stdClass|null
     */
    public function retrieveParamsDskapi()
    {
        $dskapi_cid = $this->getDskapiCid();

        $quote = $this->getQuote();
        $cartItems = $quote->getAllItems();

        // Calculate total amount from cart items
        $cartTotal = 0;
        foreach ($cartItems as $item) {
            $cartTotal += $item->getPrice() * $item->getQty();
        }

        $dskapi_price = $cartTotal;
        $dskapi_product_id = $this->resolveCartProductId();

        $dskapi_currency_code = $this->getCurrencyCode();
        switch ($this->_dskapi_eur) {
            case 0:
                break;
            case 1:
                if ($dskapi_currency_code == "EUR") {
                    $dskapi_price = number_format($dskapi_price * 1.95583, 2, ".", "");
                }
                break;
            case 2:
                if ($dskapi_currency_code == "BGN") {
                    $dskapi_price = number_format($dskapi_price / 1.95583, 2, ".", "");
                }
                break;
        }

        $response = $this->_cpApi->fetchProductApi((float) $dskapi_price, (int) $dskapi_product_id, $dskapi_cid);
        if ($response === null) {
            return null;
        }

        return json_decode(json_encode($response));
    }

    /**
     * CartButtons retrieveEur Doc Comment
     *
     * CartButtons retrieveEur function,
     *
     * @return int
     */
    public function retrieveEur()
    {
        $dskapi_cid = $this->getDskapiCid();

        $this->_curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->_curl->setOption(CURLOPT_MAXREDIRS, 3);
        $this->_curl->setOption(CURLOPT_TIMEOUT, 5);
        $this->_curl->setOption(CURLOPT_CUSTOMREQUEST, 'GET');
        $this->_curl->get(
            $this->getDskapiLiveUrl() .
                '/function/geteur.php?cid=' .
                $dskapi_cid
        );
        $response = $this->_curl->getBody();
        if ($response != null) {
            $eurdskapi = json_decode($response);
            if (json_last_error() !== JSON_ERROR_NONE || !isset($eurdskapi->dsk_eur)) {
                return 0;
            }
            return (int)$eurdskapi->dsk_eur;
        } else {
            return 0;
        }
    }

    /**
     * CartButtons getDskapiLiveUrl Doc Comment
     *
     * CartButtons getDskapiLiveUrl function,
     *
     * @return string
     */
    public function getDskapiLiveUrl()
    {
        return $this->_helper->getDskapiLiveUrl();
    }

    /**
     * CartButtons getParamsDskapi Doc Comment
     *
     * CartButtons getParamsDskapi function,
     * return params
     *
     * @return \stdClass|null
     */
    public function getParamsDskapi(): ?\stdClass
    {
        return $this->_paramsdskapi;
    }

    /**
     * CartButtons getEur Doc Comment
     *
     * CartButtons getEur function,
     * return params
     *
     * @return int
     */
    public function getEur()
    {
        return $this->_dskapi_eur;
    }

    /**
     * CartButtons getCurrencyCode Doc Comment
     *
     * CartButtons getCurrencyCode function,
     * return params
     *
     * @return string
     */
    public function getCurrencyCode(): string
    {
        $store = $this->_storeManager->getStore();
        /** @var \Magento\Store\Model\Store $store */

        if (method_exists($store, 'getCurrentCurrency') && $store->getCurrentCurrency()) {
            return (string)$store->getCurrentCurrency()->getCode();
        }

        if (method_exists($store, 'getCurrentCurrencyCode')) {
            return (string)$store->getCurrentCurrencyCode();
        }

        // Fallback to default currency code defined for the store view
        return (string)$store->getDefaultCurrencyCode();
    }

    /**
     * CartButtons getPrice Doc Comment
     *
     * CartButtons getPrice function,
     *
     * @return float
     */
    public function getPrice()
    {
        $quote = $this->getQuote();
        $cartItems = $quote->getAllItems();

        // Calculate total amount from cart items
        $cartTotal = 0;
        foreach ($cartItems as $item) {
            $cartTotal += $item->getPrice() * $item->getQty();
        }

        $dskapi_price = $cartTotal;
        $dskapi_currency_code = $this->getCurrencyCode();
        switch ($this->_dskapi_eur) {
            case 0:
                break;
            case 1:
                if ($dskapi_currency_code == "EUR") {
                    $dskapi_price = number_format($dskapi_price * 1.95583, 2, ".", "");
                }
                break;
            case 2:
                if ($dskapi_currency_code == "BGN") {
                    $dskapi_price = number_format($dskapi_price / 1.95583, 2, ".", "");
                }
                break;
        }
        return $dskapi_price;
    }

    /**
     * CartButtons getStatus Doc Comment
     *
     * CartButtons getStatus function,
     *
     * @return bool
     */
    public function getStatus()
    {
        if ($this->_paramsdskapi != null) {
            $quote = $this->getQuote();
            $cartItems = $quote->getAllItems();

            // Calculate total amount from cart items
            $cartTotal = 0;
            foreach ($cartItems as $item) {
                $cartTotal += $item->getPrice() * $item->getQty();
            }

            $dskapi_price = $cartTotal;
            $dskapi_options = boolval($this->_paramsdskapi->dsk_options ?? false);
            $dskapi_is_visible = boolval($this->_paramsdskapi->dsk_is_visible ?? false);
            $dskapi_button_status = (int)($this->_paramsdskapi->dsk_button_status ?? 0);
            return $dskapi_price > 0 &&
                $dskapi_options &&
                $dskapi_is_visible &&
                ($this->_paramsdskapi->dsk_status ?? 0) == 1 &&
                $dskapi_button_status != 0;
        } else {
            return false;
        }
    }

    /**
     * CartButtons getDskapiCid Doc Comment
     *
     * CartButtons getDskapiCid function,
     *
     * @return string
     */
    public function getDskapiCid()
    {
        $dskapi_cid = $this->_helper->getConfig('avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_cid');
        return $dskapi_cid ?: "";
    }

    /**
     * Cached proxy URL for getproductcustom (frontend AJAX).
     *
     * @return string
     */
    public function getGetProductCustomUrl(): string
    {
        return $this->getUrl('dskapipayment/index/getproductcustom');
    }

    /**
     * CartButtons getDskapiStatus Doc Comment
     *
     * CartButtons getDskapiStatus function,
     *
     * @return bool
     */
    public function getDskapiStatus()
    {
        $dskapi_status = $this->_helper->getConfig('avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_status');
        $dskapi_currency_code = $this->getCurrencyCode();
        return ($dskapi_status && ($dskapi_currency_code == "EUR" || $dskapi_currency_code == "BGN"));
    }

    /**
     * CartButtons getDskapiButtonNormalCustom Doc Comment
     *
     * CartButtons getDskapiButtonNormalCustom function,
     *
     * @return string
     */
    public function getDskapiButtonNormalCustom()
    {
        $dskapi_cid = $this->getDskapiCid();
        return $this->getDskapiLiveUrl() . '/calculators/assets/img/custom_buttons/' . $dskapi_cid . '.png';
    }

    /**
     * CartButtons getDskapiButtonHoverCustom Doc Comment
     *
     * CartButtons getDskapiButtonHoverCustom function,
     *
     * @return string
     */
    public function getDskapiButtonHoverCustom()
    {
        $dskapi_cid = $this->getDskapiCid();
        return $this->getDskapiLiveUrl() . '/calculators/assets/img/custom_buttons/' . $dskapi_cid . '_hover.png';
    }

    /**
     * CartButtons getDskapiButtonNormal Doc Comment
     *
     * CartButtons getDskapiButtonNormal function,
     *
     * @return string
     */
    public function getDskapiButtonNormal()
    {
        return $this->getDskapiLiveUrl() . '/calculators/assets/img/buttons/dsk.png';
    }

    /**
     * CartButtons getDskapiButtonHover Doc Comment
     *
     * CartButtons getDskapiButtonHover function,
     *
     * @return string
     */
    public function getDskapiButtonHover()
    {
        return $this->getDskapiLiveUrl() . '/calculators/assets/img/buttons/dsk-hover.png';
    }

    /**
     * CartButtons getDskapiSign Doc Comment
     *
     * CartButtons getDskapiSign function,
     *
     * @return string
     */
    public function getDskapiSign()
    {
        $dskapi_sign = 'лв.';
        switch ($this->_dskapi_eur) {
            case 0:
                break;
            case 1:
                $dskapi_sign = 'лв.';
                break;
            case 2:
                $dskapi_sign = 'евро';
                break;
        }
        return $dskapi_sign;
    }

    /**
     * CartButtons getVnoskiVisibleArr Doc Comment
     *
     * CartButtons getVnoskiVisibleArr function,
     *
     * @return array
     */
    public function getVnoskiVisibleArr()
    {
        $dskapi_vnoski_visible_arr = [];

        if (!$this->_paramsdskapi) {
            return $dskapi_vnoski_visible_arr;
        }

        $dskapi_vnoski_visible = (int)($this->_paramsdskapi->dsk_vnoski_visible ?? 0);
        $dskapi_vnoski_default = (int)($this->_paramsdskapi->dsk_vnoski_default ?? 0);

        for ($months = 3; $months <= 48; $months++) {
            $bit = 1 << ($months - 3);
            $isVisible = ($dskapi_vnoski_visible & $bit) !== 0;
            if (!$isVisible && $dskapi_vnoski_default === $months) {
                $isVisible = true;
            }
            $dskapi_vnoski_visible_arr[$months] = $isVisible;
        }

        return $dskapi_vnoski_visible_arr;
    }

    /**
     * CartButtons getModuleVersion Doc Comment
     *
     * CartButtons getModuleVersion function,
     *
     * @return string
     */
    public function getModuleVersion()
    {
        return $this->_helper->getModuleVersion();
    }

    /**
     * CartButtons getCartProductId Doc Comment
     *
     * CartButtons getCartProductId function,
     *
     * @return int
     */
    public function getCartProductId()
    {
        return $this->resolveCartProductId();
    }

    /**
     * Връща отстоянието над бутона (px) от настройките.
     *
     * @return int
     */
    public function getDskapiGap(): int
    {
        $value = (int)$this->_helper->getConfig('avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_gap');

        return $value >= 0 ? $value : 0;
    }
}
