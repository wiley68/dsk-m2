<?php

/**
 * StatusInterface File Doc Comment
 *
 * PHP version 8
 *
 * @category StatusInterface
 * @package  StatusInterface
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */

namespace Avalon\Dskapipayment\Api;

/**
 * StatusInterface interface Doc Comment
 *
 * StatusInterface interface
 *
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */
interface StatusInterface
{
    /**
     * OrderUpdate
     *
     * @api
     * @param string $order_id
     * @param string $status
     * @param string $calculator_id
     * @return string
     */
    public function orderUpdate($order_id, $status, $calculator_id);
}
