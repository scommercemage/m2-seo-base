<?php

/**
 * Install script will add product attribute
 *
 * @category   Scommerce
 * @package    Scommerce_SeoBase
 * @author     Sommerce Mage <core@scommerce-mage.co.uk>
 */

namespace Scommerce\SeoBase\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Model\Product;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface {

    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;
    
    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * __construct
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory, 
            \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context) {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $oldAttributeCode = 'canonical_primary_category';
        $attributeCode = 'product_primary_category';
        $groupName = 'Search Engine Optimization';
        $entityType = $eavSetup->getEntityTypeId(Product::ENTITY);
        if ($this->isProductAttributeExists($oldAttributeCode)) {

            $eavSetup->updateAttribute($entityType, $oldAttributeCode, 'attribute_code', 'product_primary_category');
            $eavSetup->updateAttribute($entityType, $oldAttributeCode, 'source_model', 'Scommerce\SeoBase\Model\Entity\Attribute\Source\Categories');
            $eavSetup->updateAttribute($entityType, $oldAttributeCode, 'frontend_label', 'Primary Category');
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
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'group' => $groupName,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => '',
                'sort_order' => 200,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'unique' => false
                    ]
            );
        }
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
    
}
