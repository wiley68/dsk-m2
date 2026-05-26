<?php

/**
 * Buttons File Doc Comment
 *
 * PHP version 8
 *
 * @category Buttons
 * @package  Buttons
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */

namespace Avalon\Dskapipayment\Block;

/**
 * Buttons Class Doc Comment
 *
 * Buttons Class
 *
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */
class Buttons extends \Magento\Framework\View\Element\Template
{
    use PopupImageUrlsTrait;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

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
     * @var \Avalon\Dskapipayment\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Avalon\Dskapipayment\Model\Service\CpApi
     */
    protected $_cpApi;

    /**
     * @var string|null
     */
    protected $_currencyCode;

    /**
     * @var int
     */
    protected $_dskapi_eur;

    /**
     * Buttons constructor Doc Comment
     *
     * Buttons Class constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Avalon\Dskapipayment\Helper\Data $helper
     * @param \Avalon\Dskapipayment\Model\Service\CpApi $cpApi
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Avalon\Dskapipayment\Helper\Data $helper,
        \Avalon\Dskapipayment\Model\Service\CpApi $cpApi,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_productRepository = $productRepository;
        $this->_request = $request;
        $this->_curl = $curl;
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
        $this->_cpApi = $cpApi;
        $this->_dskapi_eur = $this->retrieveEur();
        $this->_paramsdskapi = $this->retrieveParamsDskapi();
    }

    /**
     * Buttons getProduct Doc Comment
     *
     * Buttons getProduct function,
     * return product info
     *
     * @return mixed
     */
    public function getProduct()
    {
        try {
            $product = $this->_productRepository->getById($this->_request->getParam('id'));
            return $product;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Buttons retrieveParamsDskapi Doc Comment
     *
     * Buttons retrieveParamsDskapi function,
     * return params info
     *
     * @return mixed
     */
    public function retrieveParamsDskapi()
    {
        $dskapi_cid = $this->getDskapiCid();
        $dskapi_product = $this->getProduct();
        if (!$dskapi_product) {
            return null;
        }
        $dskapi_product_id = $dskapi_product->getId();
        $dskapi_price = (float) $this->getPrice();
        $response = $this->_cpApi->fetchProductApi($dskapi_price, (int) $dskapi_product_id, $dskapi_cid);
        if ($response === null) {
            return null;
        }

        return json_decode(json_encode($response));
    }

    /**
     * Buttons retrieveEur Doc Comment
     *
     * Buttons retrieveEur function,
     *
     * @return string
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
        $status = (int)$this->_curl->getStatus();
        if ($status < 200 || $status >= 300 || $response === null) {
            return 0;
        }
        $eurdskapi = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($eurdskapi->dsk_eur)) {
            return 0;
        }
        return (int)$eurdskapi->dsk_eur;
    }

    /**
     * Buttons getDskapiSign Doc Comment
     *
     * Buttons getDskapiSign function,
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
     * Buttons getDskapiLiveUrl Doc Comment
     *
     * Buttons getDskapiLiveUrl function,
     *
     * @return string
     */
    public function getDskapiLiveUrl()
    {
        return $this->_helper->getDskapiLiveUrl();
    }

    /**
     * Buttons getParamsDskapi Doc Comment
     *
     * Buttons getParamsDskapi function,
     * return params
     *
     * @return \stdClass|null
     */
    public function getParamsDskapi(): ?\stdClass
    {
        return $this->_paramsdskapi;
    }

    /**
     * Buttons getEur Doc Comment
     *
     * Buttons getEur function,
     * return params
     *
     * @return int
     */
    public function getEur()
    {
        return $this->_dskapi_eur;
    }

    /**
     * Buttons getCurrencyCode Doc Comment
     *
     * Buttons getCurrencyCode function,
     * return params
     *
     * @return string
     */
    public function getCurrencyCode(): string
    {
        if ($this->_currencyCode !== null) {
            return $this->_currencyCode;
        }

        $store = $this->_storeManager->getStore();
        /** @var \Magento\Store\Model\Store $store */

        if (method_exists($store, 'getCurrentCurrency') && $store->getCurrentCurrency()) {
            $this->_currencyCode = (string)$store->getCurrentCurrency()->getCode();
            return $this->_currencyCode;
        }

        if (method_exists($store, 'getCurrentCurrencyCode')) {
            $this->_currencyCode = (string)$store->getCurrentCurrencyCode();
            return $this->_currencyCode;
        }

        // Fallback to default currency code defined for the store view
        $this->_currencyCode = (string)$store->getDefaultCurrencyCode();
        return $this->_currencyCode;
    }

    /**
     * Buttons getPrice Doc Comment
     *
     * Buttons getPrice function,
     *
     * @return mixed
     */
    public function getPrice()
    {
        $product = $this->getProduct();
        if (!$product) {
            return 0;
        }

        $dskapi_price = $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();

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
     * Buttons getStatus Doc Comment
     *
     * Buttons getStatus function,
     *
     * @return bool
     */
    public function getStatus()
    {
        if ($this->_paramsdskapi != null) {
            $dskapi_price = $this->getPrice();
            $dskapi_options = boolval($this->_paramsdskapi->dsk_options);
            $dskapi_is_visible = boolval($this->_paramsdskapi->dsk_is_visible);
            $dskapi_button_status = (int)$this->_paramsdskapi->dsk_button_status;
            return $dskapi_price > 0 &&
                $dskapi_options &&
                $dskapi_is_visible &&
                $this->_paramsdskapi->dsk_status == 1 &&
                $dskapi_button_status != 0;
        } else {
            return false;
        }
    }

    /**
     * Buttons getDskapiCid Doc Comment
     *
     * Buttons getDskapiCid function,
     *
     * @return string
     */
    public function getDskapiCid()
    {
        $dskapi_cid = (string)$this->_helper->getConfig('avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_cid');
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
     * Buttons getDskapiStatus Doc Comment
     *
     * Buttons getDskapiStatus function,
     *
     * @return string
     */
    public function getDskapiStatus()
    {
        $dskapi_status = (string)$this->_helper->getConfig(
            'avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_status'
        );
        $dskapi_currency_code = $this->getCurrencyCode();
        return ($dskapi_status && ($dskapi_currency_code == "EUR" || $dskapi_currency_code == "BGN"));
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

    /**
     * Buttons getModuleVersion Doc Comment
     *
     * Buttons getModuleVersion function,
     *
     * @return string
     */
    public function getModuleVersion()
    {
        return $this->_helper->getModuleVersion();
    }

    /**
     * Buttons getVnoskiVisibleArr Doc Comment
     *
     * Buttons getVnoskiVisibleArr function,
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
     * Buttons getDskapiButtonNormalCustom Doc Comment
     *
     * Buttons getDskapiButtonNormalCustom function,
     *
     * @return string
     */
    public function getDskapiButtonNormalCustom()
    {
        $dskapi_cid = $this->getDskapiCid();
        return $this->getDskapiLiveUrl() . '/calculators/assets/img/custom_buttons/' . $dskapi_cid . '.png';
    }

    /**
     * Buttons getDskapiButtonHoverCustom Doc Comment
     *
     * Buttons getDskapiButtonHoverCustom function,
     *
     * @return string
     */
    public function getDskapiButtonHoverCustom()
    {
        $dskapi_cid = $this->getDskapiCid();
        return $this->getDskapiLiveUrl() . '/calculators/assets/img/custom_buttons/' . $dskapi_cid . '_hover.png';
    }

    /**
     * Buttons getDskapiButtonNormal Doc Comment
     *
     * Buttons getDskapiButtonNormal function,
     *
     * @return string
     */
    public function getDskapiButtonNormal()
    {
        return $this->getDskapiLiveUrl() . '/calculators/assets/img/buttons/dsk.png';
    }

    /**
     * Buttons getDskapiButtonHover Doc Comment
     *
     * Buttons getDskapiButtonHover function,
     *
     * @return string
     */
    public function getDskapiButtonHover()
    {
        return $this->getDskapiLiveUrl() . '/calculators/assets/img/buttons/dsk-hover.png';
    }
}
