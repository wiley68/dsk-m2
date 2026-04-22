<?php

/**
 * DskapiApi File Doc Comment
 *
 * PHP version 8
 *
 * @category DskapiApi
 * @package  DskapiApi
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */

namespace Avalon\Dskapipayment\Model;

/**
 * DskapiApi Class Doc Comment
 *
 * DskapiApi Class
 *
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */
class DskapiApi
{
    /**
     * @var \Magento\Framework\HTTP\Client\Curl $_httpClient
     */
    protected $_httpClient;

    /**
     * DskapiApi constructor Doc Comment
     *
     * DskapiApi Class constructor
     *
     * @param \Magento\Framework\HTTP\Client\Curl $httpClient
     */
    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $httpClient,
    ) {
        $this->_httpClient = $httpClient;
    }

    /**
     * DskapiApi fetchData Doc Comment
     *
     * DskapiApi fetchData function
     *
     * @param mixed $url
     *
     * @return string
     */
    public function fetchData($url)
    {
        $this->_httpClient->get($url);
        $response = $this->_httpClient->getBody();

        return $response;
    }
}
