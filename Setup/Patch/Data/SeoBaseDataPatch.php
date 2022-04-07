<?php

namespace Scommerce\SeoBase\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Scommerce\SeoBase\Helper\Data;


class SeoBaseDataPatch implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var EavConfig
     */
    private $eavConfig;

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


    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        EavConfig $eavConfig,
        Data $helper,
        Config $config,
        ScopeConfigInterface $scopeConfig,
        CategorySetupFactory $categorySetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->helper = $helper;
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->categorySetupFactory = $categorySetupFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $oldAttributeCode = 'canonical_primary_category';
        $attributeCode = 'product_primary_category';
        $groupName = 'Search Engine Optimization';
        $entityType = $eavSetup->getEntityTypeId(Product::ENTITY);
        if ($this->isProductAttributeExists($oldAttributeCode)) {

            $eavSetup->updateAttribute($entityType, $oldAttributeCode, 'attribute_code', 'product_primary_category');
            $eavSetup->updateAttribute($entityType, $oldAttributeCode, 'source_model', 'Scommerce\SeoBase\Model\Entity\Attribute\Source\Categories');
            $eavSetup->updateAttribute($entityType, $oldAttributeCode, 'frontend_label', 'Primary Category');
            $eavSetup->updateAttribute($entityType, $oldAttributeCode, 'backend_type', 'int');
        } else if ($this->isProductAttributeExists($attributeCode)) {
            $eavSetup->updateAttribute($entityType, $attributeCode, 'source_model', 'Scommerce\SeoBase\Model\Entity\Attribute\Source\Categories');

            // get the attribute set ids of all the attribute sets present in your Magento store
            $attributeSetIds = $eavSetup->getAllAttributeSetIds($entityType);

            foreach ($attributeSetIds as $attributeSetId) {
                $attributeGroupId = $eavSetup->getAttributeGroupId($entityType, $attributeSetId, $groupName);
                $eavSetup->addAttributeToGroup(
                    $entityType, $attributeSetId, $attributeGroupId, $attributeCode, null
                );
            }
        } else {
            /**
             * Add attributes to the eav/attribute
             */
            $eavSetup->addAttribute(
                Product::ENTITY, $attributeCode, [
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Primary Category',
                    'input' => 'select',
                    'class' => '',
                    'source' => 'Scommerce\SeoBase\Model\Entity\Attribute\Source\Categories',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'group' => $groupName,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'sort_order' => 300,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => false,
                    'unique' => false
                ]
            );
        }

        if(!$this->helper->getLicenseKey(self::SEOBASE_LICENSE_KEY)) {
            $this->copyLicenseKey();
        }

        /** @var CategorySetup $categorySetup */
        $categorySetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);
        $entityTypeId = $categorySetup->getEntityTypeId(Category::ENTITY);
        $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);

        $groups = [
            'primary_category_settings' => [
                'name' => 'Primary Category Settings', 'code' => 'primary_category_settings', 'sort' => 50, 'id' => null
            ],
        ];

        foreach ($groups as $k => $groupProp) {
            try {
                $categorySetup->getAttributeGroupId(
                    $entityTypeId,
                    $attributeSetId,
                    $groupProp['code']
                );
            } catch (LocalizedException $e) {
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
            if ($this->isCategoryAttributeExists($attributeCode)) {
                continue;
            }
            $categorySetup->addAttribute(
                $entityTypeId,
                $attributeCode,
                array_merge($attributeProp, $attributeDefaultData)
            );
            // @codingStandardsIgnoreEnd
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        //Here should go code that will revert all operations from `apply` method
        //Please note, that some operations, like removing data from column, that is in role of foreign key reference
        //is dangerous, because it can trigger ON DELETE statement
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * Returns true if attribute exists and false if it doesn't exist
     *
     * @param string $field
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function isProductAttributeExists($field)
    {
        $attr = $this->eavConfig->getAttribute(Product::ENTITY, $field);

        return ($attr && $attr->getId()) ? true : false;
    }

    /**
     * Returns true if attribute exists and false if it doesn't exist
     *
     * @param string $field
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function isCategoryAttributeExists($field)
    {
        $attr = $this->eavConfig->getAttribute(Category::ENTITY, $field);

        return ($attr && $attr->getId()) ? true : false;
    }

    /**
     * Returns true if group exists and false if it doesn't exist
     *
     * @param string $field
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function isCategoryGroupExists($field)
    {
        $attr = $this->eavConfig->getAttribute(Category::ENTITY, $field);

        return ($attr && $attr->getId()) ? true : false;
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
