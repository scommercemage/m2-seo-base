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
    
    const ENABLED              = 'scommerce_seobase/general/enabled';
    
    const LICENSE_KEY = 'scommerce_seobase/general/license_key';
    
    /**
     * @const data helper
     */
    const DATA_HELPER = '\Helper\Data';
    
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
     * @var \Magento\Catalog\Model\CategoryFactory 
     */
    protected $_categoryFactory;

    /**
     * __construct
     * 
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Module\Manager $moduleManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Module\Manager $moduleManager,
        \Scommerce\Core\Helper\Data $data,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory
    ) {
        parent::__construct($context);
        $this->_moduleManager = $moduleManager;
        $this->_data = $data;
        $this->_categoryFactory = $categoryFactory;
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
		$sku = strtolower(str_replace('\\Helper\\Data','',str_replace('Scommerce\\','',get_class($this))));
		return $this->_data->isLicenseValid($this->getLicenseKey(),$sku);
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
    
    /**
     * Get module helper class
     * 
     * @param type $modules
     * @return type
     */
    protected function getModuleDataHelper($modules) {        
        $helper = str_replace("_",'\\',$modules);
        return $helper . self::DATA_HELPER;
    }
    
    /**
     * Generating product request path
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function productRequestPath($product)
    { 
        $product = $product->load($product->getId()); // Really hack for product listing in grid/list
        $productPrimaryCategory = $product->getCustomAttribute('product_primary_category');
        if (! $productPrimaryCategory) return '';
        $primaryCategoryId = $productPrimaryCategory->getValue();
        if (is_array($primaryCategoryId)) $primaryCategoryId = end($primaryCategoryId);
        $primaryCategoryId = (int)$primaryCategoryId;
        if (empty($primaryCategoryId)) return '';
        return $this->_categoryFactory->create()->load($primaryCategoryId);

    }

}
