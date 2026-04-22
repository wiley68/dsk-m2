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
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $_curl;

    /**
     * @var \stdClass|null
     */
    protected $_paramsdskapi;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $_request_http;

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
     * @param \Magento\Framework\App\Request\Http $request_http
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Avalon\Dskapipayment\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Request\Http $request_http,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Avalon\Dskapipayment\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_curl = $curl;
        $this->_request_http = $request_http;
        $this->_storeManager = $storeManager;
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
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

        $this->_curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->_curl->setOption(CURLOPT_MAXREDIRS, 3);
        $this->_curl->setOption(CURLOPT_TIMEOUT, 6);
        $this->_curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        $this->_curl->setOption(CURLOPT_CUSTOMREQUEST, 'GET');
        $this->_curl->get(
            $this->getDskapiLiveUrl() .
                '/function/getproduct.php?cid=' .
                $dskapi_cid .
                '&price=' .
                $dskapi_price .
                '&product_id=' .
                $dskapi_product_id
        );
        $response = $this->_curl->getBody();

        if ($response != null) {
            $paramsdskapi = json_decode($response);
            if ($paramsdskapi && json_last_error() === JSON_ERROR_NONE) {
                return $paramsdskapi;
            }
        }
        return null;
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
     * CartButtons getDskapiClasses Doc Comment
     *
     * CartButtons getDskapiClasses function,
     *
     * @return \stdClass
     */
    public function getDskapiClasses(): \stdClass
    {
        $useragent =
            $this->_request_http
            ->getServer('HTTP_USER_AGENT') ? $this->_request_http->getServer('HTTP_USER_AGENT') : '';
        $dskapi_is_mobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4));
        $classes = [];
        if ($dskapi_is_mobile) {
            $classes['dskapi_PopUp_Detailed_v1'] = "dskapim_PopUp_Detailed_v1";
            $classes['dskapi_Mask'] = "dskapim_Mask";
            $classes['dskapi_picture'] =
                $this->getDskapiLiveUrl() .
                '/calculators/assets/img/dskm' .
                ($this->_paramsdskapi->dsk_reklama ?? 1) .
                '.png';
            $classes['dskapi_product_name'] = "dskapim_product_name";
            $classes['dskapi_body_panel_txt3'] = "dskapim_body_panel_txt3";
            $classes['dskapi_body_panel_txt4'] = "dskapim_body_panel_txt4";
            $classes['dskapi_body_panel_txt3_left'] = "dskapim_body_panel_txt3_left";
            $classes['dskapi_body_panel_txt3_right'] = "dskapim_body_panel_txt3_right";
            $classes['dskapi_sumi_panel'] = "dskapim_sumi_panel";
            $classes['dskapi_kredit_panel'] = "dskapim_kredit_panel";
            $classes['dskapi_body_panel_footer'] = "dskapim_body_panel_footer";
            $classes['dskapi_body_panel_left'] = "dskapim_body_panel_left";
        } else {
            $classes['dskapi_PopUp_Detailed_v1'] = "dskapi_PopUp_Detailed_v1";
            $classes['dskapi_Mask'] = "dskapi_Mask";
            $classes['dskapi_picture'] =
                $this->getDskapiLiveUrl() .
                '/calculators/assets/img/dsk' .
                ($this->_paramsdskapi->dsk_reklama ?? 1) .
                '.png';
            $classes['dskapi_product_name'] = "dskapi_product_name";
            $classes['dskapi_body_panel_txt3'] = "dskapi_body_panel_txt3";
            $classes['dskapi_body_panel_txt4'] = "dskapi_body_panel_txt4";
            $classes['dskapi_body_panel_txt3_left'] = "dskapi_body_panel_txt3_left";
            $classes['dskapi_body_panel_txt3_right'] = "dskapi_body_panel_txt3_right";
            $classes['dskapi_sumi_panel'] = "dskapi_sumi_panel";
            $classes['dskapi_kredit_panel'] = "dskapi_kredit_panel";
            $classes['dskapi_body_panel_footer'] = "dskapi_body_panel_footer";
            $classes['dskapi_body_panel_left'] = "dskapi_body_panel_left";
        }
        return (object)$classes;
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
