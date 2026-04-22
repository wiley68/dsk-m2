<?php

/**
 * Email File Doc Comment
 *
 * PHP version 8
 *
 * @category Email
 * @package  Email
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */

namespace Avalon\Dskapipayment\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Mail\Template\TransportBuilder;

/**
 * Email Class Doc Comment
 *
 * Email Class
 *
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */
class Email extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Framework\Escaper $escaper
     */
    protected $escaper;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * Email constructor Doc Comment
     *
     * Email Class constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     */
    public function __construct(
        Context $context,
        StateInterface $inlineTranslation,
        Escaper $escaper,
        TransportBuilder $transportBuilder,
    ) {
        parent::__construct($context);
        $this->inlineTranslation = $inlineTranslation;
        $this->escaper = $escaper;
        $this->transportBuilder = $transportBuilder;
        $this->logger = $context->getLogger();
    }

    /**
     * Email sendEmail Doc Comment
     *
     * Email sendEmail function,
     *
     * @param mixed $name
     * @param mixed $email
     * @param mixed $subject
     * @param mixed $body
     * @param mixed $toml
     *
     * @return bool
     */
    public function sendEmail($name, $email, $subject, $body, $toml)
    {
        try {
            $this->inlineTranslation->suspend();
            $sender = [
                'name' => $this->escaper->escapeHtml($name),
                'email' => $this->escaper->escapeHtml($email),
            ];
            $toml_arr = explode(",", $toml);
            $toml_arr_trim = array_map('trim', $toml_arr);
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('avalon_dskapipaymentmethod_tab_options_dskapi_template')
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    ]
                )
                ->setTemplateVars([
                    'body' => $body,
                    'subject' => $subject,
                ])
                ->addTo($toml_arr_trim)
                ->setFrom($sender)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            return true;
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
            return false;
        }
    }
}
