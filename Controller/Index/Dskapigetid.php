<?php

/**
 * Dskapigetid File Doc Comment
 *
 * PHP version 8
 *
 * @category Dskapigetid
 * @package  Dskapigetid
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */

namespace Avalon\Dskapipayment\Controller\Index;

use \Avalon\Dskapipayment\Helper\Data;
use \Avalon\Dskapipayment\Helper\Email;
use Magento\Sales\Model\OrderFactory;

/**
 * Dskapigetid Class Doc Comment
 *
 * Dskapigetid Class
 *
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */
class Dskapigetid implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory $_pageFactory
     */
    protected $_pageFactory;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory $_resultJsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \Magento\Checkout\Model\Session $_checkoutSession
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory $_orderFactory
     */
    protected $_orderFactory;

    /**
     * @var string $_order_id
     */
    protected $_order_id;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $_request_http;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $_curl;

    /**
     * @var \Magento\Framework\App\ResourceConnection $_resorce
     */
    protected $_resorce;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var int
     */
    protected $_dskapi_eur;

    /**
     * Dskapigetid constructor Doc Comment
     *
     * Dskapigetid Class constructor
     *
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory Order factory (generated class)
     * @phpstan-param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\Request\Http $request_http
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\Request\Http $request_http,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_pageFactory = $pageFactory;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_order_id = $this->getRealOrderId();
        $this->_request = $request;
        $this->_request_http = $request_http;
        $this->_curl = $curl;
        $this->_resorce = $resource;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_dskapi_eur = $this->retrieveEur();
    }

    /**
     * Dskapigetid getRealOrderId Doc Comment
     *
     * Dskapigetid getRealOrderId function
     *
     * @return string
     */
    public function getRealOrderId()
    {
        $order = $this->_checkoutSession->getLastRealOrder();
        $orderId = $order->getEntityId();
        return $order->getIncrementId();
    }

    /**
     * Dskapigetid getOrder Doc Comment
     *
     * Dskapigetid getOrder function
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        $order = $this->_orderFactory->create()->loadByIncrementId($this->_order_id);
        return $order;
    }

    /**
     * Dskapigetid getStorename Doc Comment
     *
     * Dskapigetid getStorename function
     *
     * @return string
     */
    public function getStorename()
    {
        return $this->_scopeConfig->getValue(
            'trans_email/ident_general/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Dskapigetid getStoreEmail Doc Comment
     *
     * Dskapigetid getStoreEmail function
     *
     * @return string
     */
    public function getStoreEmail()
    {
        return $this->_scopeConfig->getValue(
            'trans_email/ident_general/email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Dskapigetid retrieveEur Doc Comment
     *
     * Dskapigetid retrieveEur function,
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
        $eurdskapi = json_decode($response);
        return (int)$eurdskapi->dsk_eur;
    }

    /**
     * Dskapigetid getEur Doc Comment
     *
     * Dskapigetid getEur function,
     * return params
     *
     * @return int
     */
    public function getEur()
    {
        return $this->_dskapi_eur;
    }

    /**
     * Dskapigetid getDskapiCid Doc Comment
     *
     * Dskapigetid getDskapiCid function,
     *
     * @return string
     */
    public function getDskapiCid()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->create(Data::class);
        if ($helper->getConfig('avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_cid')) {
            $dskapi_cid = $helper->getConfig('avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_cid');
        } else {
            $dskapi_cid = "";
        }
        return $dskapi_cid;
    }

    /**
     * Dskapigetid getDskapiLiveUrl Doc Comment
     *
     * Dskapigetid getDskapiLiveUrl function,
     *
     * @return string
     */
    public function getDskapiLiveUrl()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->create(Data::class);
        return $helper->getDskapiLiveUrl();
    }

    /**
     * Dskapigetid execute Doc Comment
     *
     * Dskapigetid execute function
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        if ($this->_request->getParam('tag') == 'jLhrHYsfPQ3Gu9JgJPLJ') {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $helper = $objectManager->create(Data::class);
            $helper_email = $objectManager->create(Email::class);

            if ($helper->getConfig('avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_cid')) {
                $dskapi_cid = $helper->getConfig('avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_cid');
            } else {
                $dskapi_cid = "";
            }

            $dskapi_mod_version = '';
            $dskapi_mod_version = $helper->getModuleVersion();

            if ($this->getOrder()->getBillingAddress() !== null) {
                $billingstreet = $this->getOrder()->getBillingAddress()->getStreet();
            }
            if ($this->getOrder()->getShippingAddress() !== null) {
                $shippingstreet = $this->getOrder()->getShippingAddress()->getStreet();
            }

            $dskapi_price = (float)$this->getOrder()->getGrandTotal();

            if ($this->getOrder()->getCustomerId() === null) {
                $dskapi_fname =
                    $this->getOrder()
                    ->getBillingAddress() === null ?
                    '' :
                    trim($this->getOrder()->getBillingAddress()->getFirstname(), " ");
                $dskapi_lastname =
                    $this->getOrder()
                    ->getBillingAddress() === null ?
                    '' :
                    trim($this->getOrder()->getBillingAddress()->getLastname(), " ");
            } else {
                $dskapi_fname = trim($this->getOrder()->getCustomerFirstname(), " ");
                $dskapi_lastname = trim($this->getOrder()->getCustomerLastname(), " ");
            }

            $dskapi_phone =
                $this->getOrder()
                ->getBillingAddress() === null ?
                '' :
                $this->getOrder()->getBillingAddress()->getTelephone();
            $dskapi_email = $this->getOrder()->getCustomerEmail();
            $dskapi_billing_address_1 = isset($billingstreet[0]) ? $billingstreet[0] : '';
            $dskapi_billing_city =
                $this->getOrder()
                ->getBillingAddress() === null ?
                '' :
                $this->getOrder()->getBillingAddress()->getCity();
            $dskapi_shipping_address_1 = isset($shippingstreet[0]) ? $shippingstreet[0] : '';
            $dskapi_shipping_city =
                $this->getOrder()
                ->getShippingAddress() === null ?
                '' :
                $this->getOrder()->getShippingAddress()->getCity();
            $dskapi_billing_postcode = '';

            $store = $this->_storeManager->getStore();
            /** @var \Magento\Store\Model\Store $store */
            $dskapi_currency_code = '';
            if (method_exists($store, 'getCurrentCurrency') && $store->getCurrentCurrency()) {
                $dskapi_currency_code = (string)$store->getCurrentCurrency()->getCode();
            } elseif (method_exists($store, 'getCurrentCurrencyCode')) {
                $dskapi_currency_code = (string)$store->getCurrentCurrencyCode();
            } else {
                $dskapi_currency_code = (string)$store->getDefaultCurrencyCode();
            }
            $dskapi_currency_code_send = 0;
            switch ($this->getEur()) {
                case 0:
                    $dskapi_currency_code_send = 0;
                    break;
                case 1:
                    $dskapi_currency_code_send = 0;
                    if ($dskapi_currency_code == "EUR") {
                        $dskapi_price = $dskapi_price * 1.95583;
                    }
                    break;
                case 2:
                    $dskapi_currency_code_send = 1;
                    if ($dskapi_currency_code == "BGN") {
                        $dskapi_price = $dskapi_price / 1.95583;
                    }
                    break;
            }

            $products_id = '';
            $products_name = '';
            $products_q = '';
            $products_p = '';
            $products_c = '';
            $products_m = '';
            $products_i = '';
            foreach ($this->getOrder()->getAllVisibleItems() as $cart_item) {
                $dskapi_product = $cart_item->getProduct();
                $products_id .= $cart_item->getProductId();
                $products_id .= '_';
                $products_q .= $cart_item->getQtyOrdered();
                $products_q .= '_';

                $products_p_temp = (float)$cart_item->getPrice();
                switch ($this->getEur()) {
                    case 0:
                        break;
                    case 1:
                        if ($dskapi_currency_code == "EUR") {
                            $products_p_temp = $products_p_temp * 1.95583;
                        }
                        break;
                    case 2:
                        if ($dskapi_currency_code == "BGN") {
                            $products_p_temp = $products_p_temp / 1.95583;
                        }
                        break;
                }
                $products_p .= number_format($products_p_temp, 2, ".", "");
                $products_p .= '_';

                $products_name .= str_replace('"', '', str_replace("'", "", htmlspecialchars_decode($dskapi_product['name'], ENT_QUOTES)));
                $products_name .= '_';
                $cats = $dskapi_product->getCategoryIds();
                foreach ($cats as $cat_id) {
                    $dskapi_category = $cat_id;
                }
                $products_c .= $dskapi_category;
                $products_c .= '_';
                $products_m .= $dskapi_product->getAttributeText('manufacturer');
                $products_m .= '_';
                $helperImport = $objectManager->get(\Magento\Catalog\Helper\Image::class);
                $dskapi_image = $helperImport->init($dskapi_product, 'product_page_image_large')->setImageFile($dskapi_product->getFile())->getUrl();
                $dskapi_imagePath = isset($dskapi_image) ? $dskapi_image : '';
                $dskapi_imagePath_64 = base64_encode($dskapi_imagePath);
                $products_i .= $dskapi_imagePath_64;
                $products_i .= '_';
            }
            $products_id = trim($products_id, "_");
            $products_q = trim($products_q, "_");
            $products_p = trim($products_p, "_");
            $products_c = trim($products_c, "_");
            $products_m = trim($products_m, "_");
            $products_name = trim($products_name, "_");
            $products_i = trim($products_i, "_");

            $useragent =
                $this->_request_http
                ->getServer('HTTP_USER_AGENT') ? $this->_request_http->getServer('HTTP_USER_AGENT') : '';
            if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
                $dskapi_type_client = 1;
            } else {
                $dskapi_type_client = 0;
            }

            $dskapi_post = [
                'unicid' => $dskapi_cid,
                'first_name' => $dskapi_fname,
                'last_name' => $dskapi_lastname,
                'phone' => $dskapi_phone,
                'email' => $dskapi_email,
                'address2' => str_replace('"', '', str_replace("'", "", htmlspecialchars_decode($dskapi_billing_address_1, ENT_QUOTES))),
                'address2city' => str_replace('"', '', str_replace("'", "", htmlspecialchars_decode($dskapi_billing_city, ENT_QUOTES))),
                'postcode' => $dskapi_billing_postcode,
                'price' => number_format($dskapi_price, 2, ".", ""),
                'address' => str_replace('"', '', str_replace("'", "", htmlspecialchars_decode($dskapi_shipping_address_1, ENT_QUOTES))),
                'addresscity' => str_replace('"', '', str_replace("'", "", htmlspecialchars_decode($dskapi_shipping_city, ENT_QUOTES))),
                'products_id' => $products_id,
                'products_name' => $products_name,
                'products_q' => $products_q,
                'type_client' => $dskapi_type_client,
                'products_p' => $products_p,
                'version' => $dskapi_mod_version,
                'shoporder_id' => $this->_order_id,
                'products_c' => $products_c,
                'products_m' => $products_m,
                'products_i' => $products_i,
                'currency' => $dskapi_currency_code_send
            ];

            $dskapi_plaintext = json_encode($dskapi_post);
            $dskapi_publicKey = openssl_pkey_get_public($helper->readPubKey());
            $dskapi_a_key = openssl_pkey_get_details($dskapi_publicKey);
            $dskapi_chunkSize = ceil($dskapi_a_key['bits'] / 8) - 11;
            $dskapi_output = '';
            while ($dskapi_plaintext) {
                $dskapi_chunk = substr($dskapi_plaintext, 0, $dskapi_chunkSize);
                $dskapi_plaintext = substr($dskapi_plaintext, $dskapi_chunkSize);
                $dskapi_encrypted = '';
                openssl_public_encrypt($dskapi_chunk, $dskapi_encrypted, $dskapi_publicKey);
                $dskapi_output .= $dskapi_encrypted;
            }
            $dskapi_output64 = base64_encode($dskapi_output);

            // Create dskapi order i data base
            $this->_curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->_curl->setOption(CURLOPT_MAXREDIRS, 2);
            $this->_curl->setOption(CURLOPT_TIMEOUT, 5);
            $this->_curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            $this->_curl->setOption(CURLOPT_ENCODING, "");
            $this->_curl->setOption(CURLOPT_CUSTOMREQUEST, 'POST');
            $this->_curl->post(
                $helper->getDskapiLiveUrl() .
                    '/function/addorders.php',
                json_encode(['data' => $dskapi_output64])
            );
            $this->_curl->addHeader("Content-Type", "application/json");
            $this->_curl->addHeader("Content-Length", strlen(json_encode(['data' => $dskapi_output64])));
            $response = $this->_curl->getBody();
            $paramsdskapiadd = json_decode($response, true);

            if ((!empty($paramsdskapiadd)) && isset($paramsdskapiadd['order_id']) && ($paramsdskapiadd['order_id'] != 0)) {
                // save to dskapiorders file
                $connection = $this->_resorce->getConnection();
                $tableName = $connection->getTableName('dskapi_orders');
                $data = [
                    'order_id' => $this->_order_id,
                    'order_status' => 0
                ];
                $connection->insert($tableName, $data);

                if ($dskapi_type_client == 1) {
                    $redirectpath = $helper->getDskapiLiveUrl() . '/applicationm_step1.php?oid=' . $paramsdskapiadd['order_id'] . '&cid=' . $dskapi_cid;
                } else {
                    $redirectpath = $helper->getDskapiLiveUrl() . '/application_step1.php?oid=' . $paramsdskapiadd['order_id'] . '&cid=' . $dskapi_cid;
                }

                $status = 1;
                $dskapireturn = $redirectpath;
                $dskapi_send_mail = 0;
            } else {
                if (empty($paramsdskapiadd)) {
                    $connection = $this->_resorce->getConnection();
                    $tableName = $connection->getTableName('dskapi_orders');
                    $data = [
                        'order_id' => $this->_order_id,
                        'order_status' => 0
                    ];
                    $connection->insert($tableName, $data);

                    $helper_email->sendEmail(
                        $this->getStorename(),
                        $this->getStoreEmail(),
                        'Проблем комуникация заявка КП DSK Credit',
                        json_encode($dskapi_post, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                        $helper->getDskapiMail()
                    );

                    $status = 1;
                    $dskapi_send_mail = 1;
                    $dskapireturn = "";
                } else {
                    $status = 0;
                    $dskapireturn = "";
                    $dskapi_send_mail = 0;
                }
            }
        } else {
            $status = 0;
            $dskapireturn = "";
            $dskapi_send_mail = 0;
        }

        $result = $this->_resultJsonFactory->create();
        $result->setData(['msg_status' => $status, 'dskapireturn' => $dskapireturn, 'dskapi_send_mail' => $dskapi_send_mail]);
        return $result;
    }
}
