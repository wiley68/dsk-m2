<?php

/**
 * DskapiPaymentMethod File Doc Comment
 *
 * PHP version 8
 *
 * @category DskapiPaymentMethod
 * @package  DskapiPaymentMethod
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */

namespace Avalon\Dskapipayment\Model;

/**
 * DskapiPaymentMethod Class Doc Comment
 *
 * DskapiPaymentMethod Class
 *
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */
class DskapiPaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'dskapipaymentmethod';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;
}
