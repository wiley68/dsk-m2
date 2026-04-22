<?php

/**
 * DskapiTab File Doc Comment
 *
 * PHP version 8
 *
 * @category DskapiTab
 * @package  DskapiTab
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */

namespace Avalon\Dskapipayment\Block\Adminhtml\Order\View\Tab;

/**
 * DskapiTab Class Doc Comment
 *
 * DskapiTab Class, The controller that admin panel order Dsk tab
 *
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */
class DskapiTab extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'Avalon_Dskapipayment::order/view/tab/dskapi_tab.phtml';

    /**
     * @var \Magento\Framework\Registry
     */
    private $_coreRegistry;

    /**
     * @var \Magento\Framework\App\ResourceConnection $_resorce
     */
    protected $_resorce;

    /**
     * View constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\ResourceConnection $resource,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_resorce = $resource;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * Retrieve order model instance
     *
     * @return int
     * Get current id order
     */
    public function getOrderId()
    {
        return $this->getOrder()->getEntityId();
    }

    /**
     * Retrieve order increment id
     *
     * @return string
     */
    public function getOrderIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }

    /**
     * Retrieve Dsk payment
     *
     * @return bool
     */
    public function checkDskapi()
    {
        $connection = $this->_resorce->getConnection();
        $tableName = $connection->getTableName('dskapi_orders');
        $select = $connection->select()
            ->from(
                ['fo' => $tableName],
                ['id']
            )
            ->where("fo.order_id = :increment_id");
        $bind = ['increment_id' => (string)$this->getOrderIncrementId()];

        if ($connection->fetchRow($select, $bind)) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve payment data
     *
     * @param string $param
     *
     * @return string
     */
    public function getPaymentData(string $param)
    {
        $connection = $this->_resorce->getConnection();
        $tableName = $connection->getTableName('dskapi_orders');
        $select = $connection->select()
            ->from(
                ['fo' => $tableName],
                ['*']
            )
            ->where("fo.order_id = :increment_id");
        $bind = ['increment_id' => (string)$this->getOrderIncrementId()];

        $row = $connection->fetchRow($select, $bind);

        switch ($param) {
            case 'order_id':
                return $row['order_id'];
            case 'order_status':
                switch ($row['order_status']) {
                    case 0:
                        return "Създадена Апликация";
                    case 1:
                        return "Избрана финансова схема";
                    case 2:
                        return "Попълнена Апликация";
                    case 3:
                        return "Изпратен Банка";
                    case 4:
                        return "Неуспешен контакт с клиента";
                    case 5:
                        return "Анулирана апликация";
                    case 6:
                        return "Отказана апликация";
                    case 7:
                        return "Подписан договор";
                    case 8:
                        return "Усвоен кредит";
                    default:
                        return "Създадена Апликация";
                }
            default:
                return $row['order_id'];
        }
    }

    /**
     * @inheritdoc
     */
    public function getTabLabel()
    {
        return 'DSK Credit поръчки';
    }

    /**
     * @inheritdoc
     */
    public function getTabTitle()
    {
        return 'Преглед на състоянието на създадените ордери към DSK Credit';
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isHidden()
    {
        return false;
    }
}
