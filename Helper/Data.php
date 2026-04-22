<?php

/**
 * Data File Doc Comment
 *
 * PHP version 8
 *
 * @category Data
 * @package  Data
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */

namespace Avalon\Dskapipayment\Helper;

/**
 * Data Class Doc Comment
 *
 * Data Class
 *
 * @author   Ilko Ivanov <ilko.iv@gmail.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://avalonbg.com/
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $_moduleList;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $_io;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepository;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Filesystem\Io\File $io
     * @param \Magento\Framework\View\Asset\Repository $assetRepository
     */
    public function __construct(
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem\Io\File $io,
        \Magento\Framework\View\Asset\Repository $assetRepository
    ) {
        $this->_moduleList = $moduleList;
        $this->_scopeConfig = $scopeConfig;
        $this->_io = $io;
        $this->_assetRepository = $assetRepository;
    }

    /**
     * Data getConfig Doc Comment
     *
     * Data getConfig function,
     *
     * @param mixed $config_path
     *
     * @return mixed
     */
    public function getConfig($config_path)
    {
        return $this->_scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Data getDskapiLiveUrl Doc Comment
     *
     * Data getDskapiLiveUrl function,
     *
     * @return string
     */
    public function getDskapiLiveUrl()
    {
        return 'https://dsk.avalon-bg.eu';
    }

    /**
     * Data getDskapiMail Doc Comment
     *
     * Data getDskapiMail function,
     *
     * @return string
     */
    public function getDskapiMail()
    {
        return 'home@avalonbg.com';
    }

    /**
     * Data getModuleVersion Doc Comment
     *
     * Data getModuleVersion function,
     *
     * @return string
     */
    public function getModuleVersion()
    {
        $moduleInfo = $this->_moduleList->getOne('Avalon_Dskapipayment');
        return $moduleInfo['setup_version'];
    }

    /**
     * Data readPubKey Doc Comment
     *
     * Data readPubKey function,
     *
     * @return string
     */
    public function readPubKey()
    {
        try {
            if ($this->_io->fileExists($this->getPubPath())) {
                return $this->_io->read($this->getPubPath());
            }
            return '';
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __($e->getMessage())
            );
        }
    }

    /**
     * Data getPubPath Doc Comment
     *
     * Data getPubPath function,
     *
     * @return string
     */
    public function getPubPath()
    {
        $assetPub = $this->_assetRepository->createAsset("Avalon_Dskapipayment::images/pub.pem");
        return $assetPub->getSourceFile();
    }
}
