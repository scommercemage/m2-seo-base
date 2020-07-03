<?php
/**
 * SeoBase Helper
 *
 * @category   Scommerce
 * @package    Scommerce_SeoBase
 * @author     Sommerce Mage <core@scommerce-mage.co.uk>
 */

namespace Scommerce\SeoBase\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Module\Manager;
use Magento\Framework\App\ObjectManager;
use Scommerce\Core\Helper\Data as HelperData;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
/**
 * Class Data
 * @package Scommerce_SeoBase
 */
class Data extends AbstractHelper
{
    /**
     * @const config path
     */
    const EXCLUDE_CATEGORIES   = 'scommerce_url/general/exclude_categories';

    const ENABLED              = 'scommerce_seobase/general/enabled';

    const LICENSE_KEY          = 'scommerce_seobase/general/license_key';


    /**
     * @var modulesList array
     */
    protected $_modulesList = array(
        'Scommerce_CatalogUrl',
        'Scommerce_Canonical'
    );

    /**
     * @var Manager
     */
    protected $_moduleManager;

    /**
     * @var ObjectManager
     */
    protected $_objectManager;

    /**
     * @var HelperData
     */
    protected $_data;

    /**
     * @var bool
     */
    static $_seoEnabled = null;

    /**
     * __construct
     *
     * @param Context $context
     * @param Manager $moduleManager
     * @param HelperData $data
     */
    public function __construct(
        Context $context,
        Manager $moduleManager,
        HelperData $data
    ) {
        parent::__construct($context);
        $this->_moduleManager = $moduleManager;
        $this->_data = $data;
        $this->_objectManager = ObjectManager::getInstance();
    }

    /**
     * Is Catalog Url module active
     *
     * @return bool
     */
    public function isEnabled()
    {
        if (self::$_seoEnabled==null){
            $enabled = $this->isSetFlag(self::ENABLED);
            self::$_seoEnabled = $this->isCliMode() ? $enabled : $enabled && $this->isLicenseValid();
        }
        return self::$_seoEnabled;
    }

    /**
     * Returns license key administration configuration option
     *
     * @return string
     */
    public function getLicenseKey()
    {
        return $this->getValue(self::LICENSE_KEY);
    }

    /**
     * returns whether license key is valid or not
     *
     * @return bool
     */
    public function isLicenseValid()
    {
        $sku = strtolower(str_replace('\\Helper\\Data', '', str_replace('Scommerce\\', '', get_class())));
        return $this->_data->isLicenseValid($this->getLicenseKey(), $sku);
    }

    /**
     * Helper method for retrieve config value by path and scope
     *
     * @param string $path The path through the tree of configuration values, e.g., 'general/store_information/name'
     * @param string $scopeType The scope to use to determine config value, e.g., 'store' or 'default'
     * @param null|string $scopeCode
     * @return mixed
     */
    protected function getValue($path, $scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        return $this->scopeConfig->getValue($path, $scopeType, $scopeCode);
    }

    /**
     * Helper method for retrieve config flag by path and scope
     *
     * @param string $path The path through the tree of configuration values, e.g., 'general/store_information/name'
     * @param string $scopeType The scope to use to determine config value, e.g., 'store' or 'default'
     * @param null|string $scopeCode
     * @return bool
     */
    protected function isSetFlag($path, $scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        return $this->scopeConfig->isSetFlag($path, $scopeType, $scopeCode);
    }

    /**
     * Check if running in cli mode
     *
     * @return bool
     */
    protected function isCliMode()
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Get exclude categories ids
     *
     * @return string|null '1,4,6' etc
     */
    public function getExcludeCategories()
    {
        return $this->getValue(self::EXCLUDE_CATEGORIES);
    }

    /**
     * Returns if module exists or not
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isChildModuleEnabled() {
        $catalogUrlActive = $this->isScommerceCatalogUrlModuleEnabled();
        $canonicalUrlActive = $this->isScommerceCanonicalModuleEnabled();

        if ($catalogUrlActive || $canonicalUrlActive) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns if module exists or not
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isScommerceCatalogUrlModuleEnabled() {
        $enable = $this->_moduleManager->isEnabled('Scommerce_CatalogUrl');
        if ($enable) {
            $catalogUrlhelper = $this->_objectManager->get('Scommerce\CatalogUrl\Helper\Data');
            return $catalogUrlhelper->isCatalogUrlActive();
        }
    }


    /**
     * Returns if module exists or not
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isScommerceCanonicalModuleEnabled() {
        $enable = $this->_moduleManager->isEnabled('Scommerce_Canonical');
        if ($enable) {
            $helper = $this->_objectManager->get('Scommerce\Canonical\Helper\Data');
            return $helper->isEnabled();
        }
    }
}