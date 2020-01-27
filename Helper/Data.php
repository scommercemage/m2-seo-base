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

/**
 * Class Data
 * @package Scommerce_SeoBase
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @const config path
     */
    const EXCLUDE_CATEGORIES   = 'scommerce_url/general/exclude_categories';
    
    const ENABLED              = 'scommerce_seobase/general/enabled';
    
    const LICENSE_KEY = 'scommerce_seobase/general/license_key';
      
   
    /**
     * @var modulesList array
     */
    protected $_modulesList = array(
                                'Scommerce_CatalogUrl',
                                'Scommerce_Canonical'
                                );

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $_moduleManager;
    
    /**
     * @var \Magento\Framework\App\ObjectManager::getInstance()
     */
    protected $_objectManager;    
    
    /**
     * @var \Scommerce\Core\Helper\Data
     */
    protected $_data;

    /**
     * __construct
     * 
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Module\Manager $moduleManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Module\Manager $moduleManager,
        \Scommerce\Core\Helper\Data $data
    ) {
        parent::__construct($context);
        $this->_moduleManager = $moduleManager;
        $this->_data = $data;
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }
    
    
    /**
     * Is Catalog Url module active
     *
     * @return bool
     */
    public function isEnabled()
    {
        $enabled = $this->isSetFlag(self::ENABLED);
        return $this->isCliMode() ? $enabled : $enabled && $this->isLicenseValid();
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
    public function isLicenseValid(){
        
        $seoModuleSkus = array('seobase', 
            'seosuite', 
            'hreflang', 
            'seositemap', 
            'richsnippet', 
            'catalogurl', 
            'canonical', 
            'crosslinking');
        $isValid = false;
        foreach ($seoModuleSkus as $sku) {
            $isValid = $this->_data->isLicenseValid($this->getLicenseKey(), $sku);
            if ($isValid)
                break;
        }
        return $isValid;

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
     * @throws \Magento\Framework\Exception\LocalizedException
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
     * @throws \Magento\Framework\Exception\LocalizedException
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isScommerceCanonicalModuleEnabled() {
        $enable = $this->_moduleManager->isEnabled('Scommerce_Canonical');
        if ($enable) {
            $helper = $this->_objectManager->get('Scommerce\Canonical\Helper\Data');
            return $helper->isEnabled();
        }
    }
}
