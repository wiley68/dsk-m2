<?php

/**
 * Dskapipanel File Doc Comment
 *
 * PHP version 8
 *
 * @category Dskapipanel
 * @package  Dskapipanel
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */

namespace Avalon\Dskapipayment\Block;

use \Avalon\Dskapipayment\Helper\Data;

/**
 * Dskapipanel Class Doc Comment
 *
 * Dskapipanel Class
 *
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */
class Dskapipanel extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $_request;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $_curl;

    /**
     * @var \stdClass|null
     */
    protected $_paramsdskapi;

    /**
     * Dskapipanel constructor Doc Comment
     *
     * Dskapipanel Class constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        parent::__construct($context);
        $this->_request = $request;
        $this->_scopeConfig = $scopeConfig;
        $this->_curl = $curl;
        $this->_paramsdskapi = $this->retrieveParamsDskapi();
    }

    /**
     * Dskapipanel getParamsDskapi Doc Comment
     *
     * Dskapipanel getParamsDskapi function,
     * return params
     *
     * @return \stdClass|null
     */
    public function getParamsDskapi()
    {
        return $this->_paramsdskapi;
    }

    /**
     * Dskapipanel retrieveParamsDskapi Doc Comment
     *
     * Dskapipanel retrieveParamsDskapi function,
     * return params info
     *
     * @return mixed
     */
    public function retrieveParamsDskapi()
    {
        $dskapi_cid = $this->getDskapiCid();

        $this->_curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->_curl->setOption(CURLOPT_MAXREDIRS, 2);
        $this->_curl->setOption(CURLOPT_TIMEOUT, 6);
        $this->_curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        $this->_curl->setOption(CURLOPT_CUSTOMREQUEST, 'GET');
        $this->_curl->get($this->getDskapiLiveUrl() . '/function/getrek.php?cid=' . $dskapi_cid);
        $response = $this->_curl->getBody();
        $paramsdskapi = json_decode($response);
        return $paramsdskapi;
    }

    /**
     * Dskapipanel getDskapiCid Doc Comment
     *
     * Dskapipanel getDskapiCid function,
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
     * Dskapipanel getDskapiLiveUrl Doc Comment
     *
     * Dskapipanel getDskapiLiveUrl function,
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
     * Dskapipanel isHomePage Doc Comment
     *
     * Dskapipanel isHomePage function,
     *
     * @return bool
     */
    public function isHomePage()
    {
        $currentUrl = trim($this->_request->getPathInfo(), '/');
        $homeUrlPath =
            trim($this->_scopeConfig
                ->getValue('web/default/front', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), '/');
        return $currentUrl === $homeUrlPath || $currentUrl === '';
    }

    /**
     * Dskapipanel getHelper Doc Comment
     *
     * Dskapipanel getHelper function,
     *
     * @return mixed
     */
    public function getHelper()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->create(Data::class);
        return $helper;
    }

    /**
     * Dskapipanel isStatus Doc Comment
     *
     * Dskapipanel isStatus function,
     *
     * @return bool
     */
    public function isStatus()
    {
        if ($this->getHelper()->getConfig('avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_status')) {
            $dskapi_status =
                $this->getHelper()
                ->getConfig('avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_status');
        } else {
            $dskapi_status = "";
        }
        if ($this->getHelper()->getConfig('avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_reklama')) {
            $dskapi_reklama =
                $this->getHelper()
                ->getConfig('avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_reklama');
        } else {
            $dskapi_reklama = "";
        }

        return $dskapi_status == "1" && $dskapi_reklama == "1" && $this->isHomePage();
    }

    /**
     * Dskapipanel isServerStatus Doc Comment
     *
     * Dskapipanel isServerStatus function,
     *
     * @return bool
     */
    public function isServerStatus()
    {
        return !empty($this->_paramsdskapi) &&
            $this->_paramsdskapi->dsk_status == 1 &&
            $this->_paramsdskapi->dsk_container_status == 1;
    }

    /**
     * Dskapipanel getDskapiDeviceIs Doc Comment
     *
     * Dskapipanel getDskapiDeviceIs function,
     *
     * @return string
     */
    public function getDskapiDeviceIs()
    {
        $useragent =
            $this->_request
            ->getServer('HTTP_USER_AGENT') ? $this->_request->getServer('HTTP_USER_AGENT') : '';
        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
            $dskapi_deviceis = "Yes";
        } else {
            $dskapi_deviceis = "No";
        }
        return $dskapi_deviceis;
    }
}
