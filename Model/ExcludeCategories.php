<?php
/**
 * Category list
 *
 * @category   Scommerce
 * @package    Scommerce_SeoBase
 * @author     Sommerce Mage <core@scommerce-mage.co.uk>
 */

namespace Scommerce\SeoBase\Model;

/**
 * Class ExcludeCategories
 * @package Scommerce_SeoBase
 */
class ExcludeCategories implements \Magento\Framework\Option\ArrayInterface
{
    /* @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory */
    protected $categoryCollectionFactory;

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $options = [];
        $options[] = [
            'value' => '',
            'label' => ' '
        ];

        foreach ($this->toArray() as $key => $value) {
            $options[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $options;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function toArray()
    {
        $categories = [];
        foreach ($this->getCategoryCollection(1) as $category) {
            /* @var \Magento\Catalog\Model\ResourceModel\Category $category */
            $categories[$category->getEntityId()] = $category->getName();
        }
        return $categories;
    }

    /**
     * Returns categories of the certain level
     *
     * @param int $level
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
     * @throws \Exception
     */
    protected function getCategoryCollection($level = 1)
    {
        /* @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->categoryCollectionFactory->create();
        return $collection
            ->addAttributeToSelect('*')
            ->addLevelFilter($level);
    }

}
