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
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Scommerce\SeoBase\Helper\Data;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\InstallDataInterface;
use Zend_Validate_Exception;

/**
 * Upgrade Data script
 *
 *  @package Scommerce_SeoBase
 */
class UpgradeData implements UpgradeDataInterface {

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CategorySetupFactory
     */
    private $categorySetupFactory;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var array
     */
    protected $configData = array();

    /**
     * @var array
     */
    protected $_licenseKey = array('scommerce_canonical/general/license_key',
       'scommerce_url/general/license_key',
       'scommerce_google_cards/general/license_key' );

    /**
     * @const config path
     */
    const SEOBASE_LICENSE_KEY = 'scommerce_seobase/general/license_key';

    /**
     * UpgradeData constructor.
     * @param Config $config
     * @param Data $helper
     * @param ScopeConfigInterface $scopeConfig
     * @param CategorySetupFactory $categorySetupFactory
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        Config $config,
        Data $helper,
        ScopeConfigInterface $scopeConfig,
        CategorySetupFactory $categorySetupFactory,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->config = $config;
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * Upgrade script
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if(!$this->helper->getLicenseKey(self::SEOBASE_LICENSE_KEY)) {
            $this->copyLicenseKey();
        }
        
        if (version_compare($context->getVersion(), '2.0.12', '<')) {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $attributeCode = 'product_primary_category';
            $groupName = 'Search Engine Optimization';
            $entityType = $eavSetup->getEntityTypeId(Product::ENTITY);
            $eavSetup->updateAttribute($entityType, $attributeCode, 'source_model', 'Scommerce\SeoBase\Model\Entity\Attribute\Source\Categories');
            $eavSetup->updateAttribute($entityType, $attributeCode, 'group', $groupName);
            $eavSetup->updateAttribute($entityType, $attributeCode, 'global', \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE);
            
        }
        
        if (version_compare($context->getVersion(), '2.0.11', '<')) {
            /** @var CategorySetup $categorySetup */
            $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);
            $entityTypeId = $categorySetup->getEntityTypeId(Category::ENTITY);
            $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);

            $groups = [
                'primary_category_settings' => [
                    'name' => 'Primary Category Settings', 'code' => 'primary_category_settings', 'sort' => 50, 'id' => null
                ],
            ];

            foreach ($groups as $k => $groupProp) {
                $categorySetup->addAttributeGroup(
                    $entityTypeId,
                    $attributeSetId,
                    $groupProp['name'],
                    $groupProp['sort']
                );
                $groups[$k]['id'] = $categorySetup->getAttributeGroupId(
                    $entityTypeId,
                    $attributeSetId,
                    $groupProp['code']
                );
            }

            $attributes = [
                'exclude_from_primary_category' => [
                    'group' => 'primary_category_settings',
                    'sort'  => 10,
                    'type'  => 'int',
                    'input' => 'boolean',
                    'label' => 'Exclude From Primary Category'
                ],
                'primary_category_priority' => [
                    'group' => 'primary_category_settings',
                    'sort'  => 20,
                    'type'  => 'int',
                    'input' => 'text',
                    'default' => 0,
                    'label' => 'Priority'
                ],
            ];

            $attributeDefaultData = [
                'visible'                 => true,
            ];

            foreach ($attributes as $attributeCode => $attributeProp) {
                // @codingStandardsIgnoreStart
                $categorySetup->addAttribute(
                    $entityTypeId,
                    $attributeCode,
                    array_merge($attributeProp, $attributeDefaultData)
                );
                // @codingStandardsIgnoreEnd
            }
        }
    }

    /**
     * Copy License key from old module to current module
     */
    protected function copyLicenseKey()
    {
        if ($key = $this->getLicenseKey()) {
            $this->config->saveConfig(self::SEOBASE_LICENSE_KEY, $key, 'default', 0);
        }
    }

    /**
     * Check, if old modules have license key
     * @return false| string
     */
    protected function getLicenseKey()
    {
        foreach ($this->_licenseKey as $key) {
            if ($licenseKey = $this->scopeConfig->getValue($key)) {
                return $licenseKey;
            }
        }
    }

}
