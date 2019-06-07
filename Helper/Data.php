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
    const EXCLUDE_CATEGORIES   = 'scommerce_seobase/general/exclude_categories';
    
    /**
     * @const data helper
     */
    const DATA_HELPER = '\Helper\Data';
    
    /**
     * @var modulesList array
     */
    protected $_modulesList = array(
                                'Scommerce_CatalogUrl',
                                'Scommerce_SeoSitemap', 
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
     * __construct
     * 
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Module\Manager $moduleManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Module\Manager $moduleManager
    ) {
        parent::__construct($context);
        $this->_moduleManager = $moduleManager;
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
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
     * Returns if module exists or not
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isChildModuleEnabled() {

        foreach ($this->_modulesList as $modules) {

            if ($this->_moduleManager->isEnabled($modules)) {                
                $moduleDataHelper = $this->getModuleDataHelper($modules);
                $helper = $this->_objectManager->create($moduleDataHelper);
                if($isActive = $helper->isEnabled()){
                    return $isActive;
                }
            }
        }
    }
    
    protected function getModuleDataHelper($modules) {        
        $helper = str_replace("_",'\\',$modules);
        return $helper . self::DATA_HELPER;
    }

}
