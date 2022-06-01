<?php

namespace Scommerce\SeoBase\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;


class ProductDataInstall implements DataPatchInterface
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
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        \Magento\Eav\Model\Config $eavConfig,
        EavSetupFactory          $eavSetupFactory
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavConfig = $eavConfig;
        $this->eavSetupFactory = $eavSetupFactory;
    }

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
            $eavSetup->updateAttribute($entityType, $oldAttributeCode, 'source_model', 'Scommerce\SeoBase\Model\Entity\Attribute\Source\Categories');
            $eavSetup->updateAttribute($entityType, $oldAttributeCode, 'frontend_label', 'Primary Category');
            $eavSetup->updateAttribute($entityType, $oldAttributeCode, 'backend_type', 'int');
            $eavSetup->updateAttribute($entityType, $oldAttributeCode, 'group', $groupName);
            $eavSetup->updateAttribute($entityType, $oldAttributeCode, 'global', \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE);
            $eavSetup->updateAttribute($entityType, $oldAttributeCode, 'attribute_code', 'product_primary_category');
        } else if ($this->isProductAttributeExists($attributeCode)) {
            $eavSetup->updateAttribute($entityType, $attributeCode, 'source_model', 'Scommerce\SeoBase\Model\Entity\Attribute\Source\Categories');
            $eavSetup->updateAttribute($entityType, $attributeCode, 'group', $groupName);
            $eavSetup->updateAttribute($entityType, $attributeCode, 'global', \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE);

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

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    protected function isProductAttributeExists($field)
    {
        $attr = $this->eavConfig->getAttribute(Product::ENTITY, $field);

        return ($attr && $attr->getId()) ? true : false;
    }


    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }
}
