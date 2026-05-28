<?php

/**
 * Status File Doc Comment
 *
 * PHP version 8
 *
 * @category Status
 * @package  Status
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */

namespace Avalon\Dskapipayment\Model;

use \Avalon\Dskapipayment\Helper\Data;
use Avalon\Dskapipayment\Model\Service\CpApi;

/**
 * Status Class Doc Comment
 *
 * Status Class
 *
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */
class Status implements \Avalon\Dskapipayment\Api\StatusInterface
{
    /**
     * @var \Magento\Framework\App\ResourceConnection $_resorce
     */
    protected $_resorce;

    /**
     * @var CpApi
     */
    private $cpApi;

    /**
     * Status constructor Doc Comment
     *
     * Status Class constructor
     *
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        CpApi $cpApi
    ) {
        $this->_resorce = $resource;
        $this->cpApi = $cpApi;
    }

    /**
     * Status getConfig Doc Comment
     *
     * Status getConfig function,
     *
     * @param string $order_id
     * @param string $status
     * @param string $calculator_id
     *
     * @return string|false
     */
    public function orderUpdate($order_id, $status, $calculator_id)
    {
        $json = [];
        $json['success'] = 'unsuccess';

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->create(Data::class);

        if ($helper->getConfig('avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_cid')) {
            $dskapi_cid = $helper->getConfig('avalon_dskapipaymentmethod_tab_options/properties_dskapi/dskapi_cid');
        } else {
            $dskapi_cid = "";
        }

        if (($calculator_id != '') && ($dskapi_cid == $calculator_id)) {
            $connection = $this->_resorce->getConnection();
            $tableName = $connection->getTableName('dskapi_orders');
            $data = [
                'order_status' => $status
            ];
            $where = ['order_id = ?' => $order_id];
            $connection->update($tableName, $data, $where);
            $json['success'] = 'success';
        }

        $json['order_id'] = $order_id;
        $json['status'] = $status;
        $json['calculator_id'] = $calculator_id;

        return json_encode($json, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param string $cid
     * @return array
     */
    public function clearCache($cid)
    {
        if (!$this->cpApi->isRequestFromControlPanel()) {
            return [
                'ok' => false,
                'status' => 403,
                'message' => 'Forbidden',
            ];
        }

        $payload = $this->cpApi->processClearCacheRequest((string) $cid);
        $httpStatus = (int) ($payload['_http_status'] ?? 200);
        unset($payload['_http_status']);

        $isSuccess = (bool) ($payload['success'] ?? false);

        return [
            'ok' => $isSuccess,
            'status' => $httpStatus,
            'message' => $isSuccess
                ? ('Cache cleared. Deleted records: ' . (int) ($payload['deleted'] ?? 0))
                : (string) ($payload['message'] ?? 'Forbidden'),
        ];
    }
}
