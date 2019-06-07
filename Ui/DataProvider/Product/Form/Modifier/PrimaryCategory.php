<?php
/**
 * Scommerce_SeoBase Data Modifier
 *
 * Copyright © 2019 Scommerce Mage. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Scommerce\SeoBase\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Scommerce\SeoBase\Helper\Data;

/**
 * Class PrimaryCategory
 *
 *  @package Scommerce_SeoBase
 */
class PrimaryCategory extends AbstractModifier
{
   
    /** 
    * @var \Scommerce\SeoBase\Helper\Data
    */
    protected $_helper;

    /**
     * __construct
     * 
     * @param Data $data
     */
    public function __construct(
        Data $data
    ) {
        $this->_helper = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        
        return $this->_customizeCategoryField($meta);
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * Hide Primary category section, if module is not active
     *
     * @param array $meta
     * @return array
     */
    protected function _customizeCategoryField(array $meta)
    {
        $primaryCategoryGroup = 'primary-category';
        
        if(!$this->_helper->isChildModuleEnabled()){
            $meta = array_replace_recursive(
            $meta,
                [
                    $primaryCategoryGroup => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'visible' => false
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }

        return $meta;
    }
    
}