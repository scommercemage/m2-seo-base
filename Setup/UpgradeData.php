<?php

/**
 * Upgrade script will add product attribute
 *
 * @category   Scommerce
 * @package    Scommerce_SeoBase
 * @author     Sommerce Mage <core@scommerce-mage.co.uk>
 */

namespace Scommerce\SeoBase\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * Upgrade Data script
 *
 *  @package Scommerce_SeoBase
 */
class UpgradeData implements UpgradeDataInterface {
    
    /**
     * @var \Scommerce\SeoBase\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Config\Model\Config
     */
    private $config;
    
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    
    
   protected $configData = array(); 
        
   protected $_licenseKey = array('scommerce_canonical/general/license_key',
       'scommerce_url/general/license_key',
       'scommerce_google_cards/general/license_key' );
    
    /**
     * @const config path
     */
    const SEOBASE_LICENSE_KEY = 'scommerce_seobase/general/license_key';
    
    /**
     * Constructor
     * 
     * @param \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        \Magento\Config\Model\Config $config,
        \Scommerce\SeoBase\Helper\Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    
    ) {
        $this->config = $config;
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
    }
   
    /**
     * Upgrade script
     * 
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context) {
        if(!$this->helper->getLicenseKey(self::SEOBASE_LICENSE_KEY)) {
            $this->copyLicenseKey();
        }
    }
    
    /**
     * Copy License key from old module to current module
     */
    protected function copyLicenseKey() {

        if ($key = $this->getLicenseKey()) {
            $this->config->setDataByPath(self::SEOBASE_LICENSE_KEY, $key);
            $this->config->save();
        }
    }

    /**
     * Check, if old modules have license key
     * @return false| string
     */
    protected function getLicenseKey() {

        foreach ($this->_licenseKey as $key) {
            if ($licenseKey = $this->scopeConfig->getValue($key)) {
                return $licenseKey;
            } 
        }
    }

}
