<?php
/**
 * Magento\Catalog\Model\Product\Url descendant to get protected fields
 *
 * @category   Scommerce
 * @package    Scommerce_SeoBase
 * @author     Sommerce Mage <core@scommerce-mage.co.uk>
 */
namespace Scommerce\SeoBase\Model\Product;
/**
 * Class Url
 * @package Scommerce\SeoBase\Model\Product
 */
class Url
{
    /* @var \Magento\Catalog\Model\CategoryFactory */
    protected $categoryFactory;

    public function __construct(
        \Magento\Catalog\Model\CategoryFactory $categoryFactory
    ) {
        $this->categoryFactory = $categoryFactory;
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
        return $this->categoryFactory->create()->load($primaryCategoryId);

    }
}