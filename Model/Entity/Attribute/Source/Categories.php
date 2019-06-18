<?php
/**
 * Category list
 *
 * @category   Scommerce
 * @package    Scommerce_SeoBase
 * @author     Sommerce Mage <core@scommerce-mage.co.uk>
 */

namespace Scommerce\SeoBase\Model\Entity\Attribute\Source;

/**
 * Class Categories
 * @package Scommerce_SeoBase
 */
class Categories extends \Magento\Eav\Model\Entity\Attribute\Source\Table
{
    /** @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory */
    protected $_categoryCollectionFactory;

    /** @var \Magento\Framework\Registry */
    protected $_registry;

    /** @var \Scommerce\SeoBase\Helper\Data */
    protected $helper;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Scommerce\CatalogUrl\Helper\Data $helper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Framework\Registry $registry,
        \Scommerce\CatalogUrl\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_registry = $registry;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
    }

     /**
      * Get the list of all categories
      *
      * @return array
      * @throws \Exception
      */
     public function getCategories()
     {
         $categoryOption = array(['label' => __('Please select primary category'), 'value' => '']);

         // If product exists then retrieve only associated categories
         /** @var \Magento\Catalog\Model\Product $product */
         $product = $this->_registry->registry('product');

         // Retrieve excluded categories selected in system configuration settings
         // If selection found apply it to filter category collection
         $categories = $this->addExcludeCategories($this->getPreparedCategoryCollection());

         // If product has categories then load only those categories
         $categoryIds = $product ? $product->getCategoryIds() : [];
         if (! empty($categoryIds)) {
             $categories->addAttributeToFilter('entity_id', ['in' => $categoryIds]);
         }

         // Load category collection at the end to apply all the above filters
         $categories->load();

         // Loop through loaded categories collection
         foreach ($categories as $cat) {
             /** @var \Magento\Catalog\Model\Category|\Magento\Catalog\Model\ResourceModel\Category $cat */
             // Loading collection with all the parent category ids using getPathIds
             // In simple terms it will load all parent categories associated with this category
             $childCategories = $this->createCategoryCollection()
                 ->addAttributeToSelect('name')
                 ->setStoreId($this->storeManager->getStore()->getId())
                 ->addAttributeToFilter('entity_id', ['in' => $cat->getPathIds()])
                 ->addOrderField('level');

             $fullCategoryPath = [];

             // Concatenating the whole path using name instead of id
             foreach ($childCategories as $col) {
                 /* @var \Magento\Catalog\Model\Category|\Magento\Catalog\Model\ResourceModel\Category $col */
                 if ($col->getName()) $fullCategoryPath[] = $col->getName();
             }
             if ($fullCategoryPath) {
                 $label = implode(' -> ', $fullCategoryPath);
                 $categoryOption[] = ['value' => $cat->getId(), 'label' => $label];
             }

         }

         return $categoryOption;
     }

    /**
     * Get all options
     *
     * @param bool $withEmpty Just for compability with parent method signature
     * @param bool $defaultValues Just for compability with parent method signature
     * @return array
     * @throws \Exception
     */
    public function getAllOptions($withEmpty = true, $defaultValues = false)
    {
        if (! $this->_options) {
            $this->_options = $this->getCategories();
        }
        return $this->_options;
    }

    /**
     * Get a text for option value
     *
     * @param string|integer $value
     * @return string|bool
     * @throws \Exception
     */
    public function getOptionText($value)
    {
        foreach ($this->getAllOptions() as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }

    /**
     * Retrieve flat column definition
     *
     * @return array
     */
    public function getFlatColumns()
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        return [
            $attributeCode => [
                'unsigned' => false,
                'default' => null,
                'extra' => null,
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => true,
                'comment' => __('Custom Attribute Options  ') . $attributeCode . __(' column'),
            ],
        ];
    }

    /**
     * Helper for get prepared category collection
     * Get all the categories for the particular store and sorting them by level and parent_id
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
     * @throws \Exception
     */
    protected function getPreparedCategoryCollection()
    {
        return $this->createCategoryCollection()
            ->addAttributeToSelect('name')
            ->addOrderField('level')
            ->addOrderField('parent_id');
    }

    /**
     * Helper to add exclude categories to collection
     *
     * @param \Magento\Catalog\Model\ResourceModel\Category\Collection $categories
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
     * @throws \Exception
     */
    protected function addExcludeCategories($categories)
    {
        if (empty($this->helper->getExcludeCategories())) {
            return $categories;
        }

        $excludeCategories = explode(',', $this->helper->getExcludeCategories());
        $categories->addAttributeToFilter('entity_id', ['nin' => $excludeCategories]);

        // All making sure that none of the child get loaded for excluded root categories
        $sqlQuery = [];
        foreach($excludeCategories as $excludeCategory) {
            $sqlQuery[] = ' INSTR(path,"/' . $excludeCategory . '/")=0 ';
        }
        $categories->getSelect()->where(implode(' AND ', $sqlQuery));

        return $categories;
    }

    /**
     * Helper for creating instance of category collection
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    protected function createCategoryCollection()
    {
        return $this->_categoryCollectionFactory->create();
    }
}
