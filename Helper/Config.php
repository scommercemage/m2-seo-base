<?php
/**
 * SeoBase Helper Config
 *
 * @category   Scommerce
 * @package    Scommerce_SeoBase
 * @author     Sommerce Mage <core@scommerce-mage.co.uk>
 */

namespace Scommerce\SeoBase\Helper;

/**
 * Class Config
 * @package Scommerce_SeoBase
 */
class Config extends \Magento\Framework\App\Helper\AbstractHelper {
    
    
    /**
     * @var array 
     */
    protected $_canonicalCongfigData = 	array('scommerce_seobase/general_canonical/active'=> 'scommerce_canonical/general/active',
	'scommerce_seobase/category_canonical_tags/category_enabled' => 'scommerce_canonical/category_canonical_tags/category_enabled',
	'scommerce_seobase/category_canonical_tags/exclude_current_category' => 'scommerce_canonical/category_canonical_tags/exclude_current_category',
	'scommerce_seobase/category_canonical_tags/append_text_with_every_category' => 'scommerce_canonical/category_canonical_tags/append_text_with_every_category',
	'scommerce_seobase/category_canonical_tags/append_text' => 'scommerce_canonical/category_canonical_tags/append_text',
	'scommerce_seobase/product_canonical_tags/product_enabled'=> 'scommerce_canonical/product_canonical_tags/product_enabled',
	'scommerce_seobase/product_canonical_tags/include_category_path'=> 'scommerce_canonical/product_canonical_tags/include_category_path',
	'scommerce_seobase/cms_canonical_tags/cms_enabled' => 'scommerce_canonical/cms_canonical_tags/cms_enabled',
	'scommerce_seobase/noindex_nofollow/nofollow_enabled' => 'scommerce_canonical/noindex_nofollow/nofollow_enabled',
	'scommerce_seobase/noindex_nofollow/action_names' => 'scommerce_canonical/noindex_nofollow/action_names',
	'scommerce_seobase/noindex_nofollow/route_names' => 'scommerce_canonical/noindex_nofollow/route_names');
    
    /**
     * @var array 
     */
    protected $_catalogConfigData = array('scommerce_seobase/general_catalogurl/enabled' => 'scommerce_url/general/enabled',
        'scommerce_seobase/general/exclude_categories' => 'scommerce_seobase/general/exclude_categories',
        'scommerce_seobase/general_catalogurl/remove_category_path' => 'scommerce_url/general/remove_category_path');
     
    /**
     * @var array 
     */
    protected $_richSnippetConfigData = array('scommerce_google_cards/general/enable' => 'scommerce_google_cards/general/enable',
        'scommerce_google_cards/general/description' => 'scommerce_google_cards/general/description',
        'scommerce_google_cards/general/price' => 'scommerce_google_cards/general/price',
        'scommerce_google_cards/general/brand' => 'scommerce_google_cards/general/brand',
        'scommerce_google_cards/rich_snippet/enable' => 'scommerce_google_cards/rich_snippet/enable',
        'scommerce_google_cards/rich_snippet/description' => 'scommerce_google_cards/rich_snippet/description',
        'scommerce_google_cards/rich_snippet/price' => 'scommerce_google_cards/rich_snippet/price',
        'scommerce_google_cards/rich_snippet/wrap_with_div' => 'scommerce_google_cards/rich_snippet/wrap_with_div',
        'scommerce_schemas/twitter_cards/enable' => 'scommerce_schemas/twitter_cards/enable',
        'scommerce_schemas/twitter_cards/description' => 'scommerce_schemas/twitter_cards/description',
        'scommerce_schemas/twitter_cards/card_type' => 'scommerce_schemas/twitter_cards/card_type',
        'scommerce_schemas/twitter_cards/price' => 'scommerce_schemas/twitter_cards/price',
        'scommerce_schemas/twitter_cards/site' => 'scommerce_schemas/twitter_cards/site',
        'scommerce_schemas/twitter_cards/creator' => 'scommerce_schemas/twitter_cards/creator',
        'scommerce_schemas/facebook_opengraph/enable' => 'scommerce_schemas/facebook_opengraph/enable',
        'scommerce_schemas/facebook_opengraph/description' => 'scommerce_schemas/facebook_opengraph/description',
        'scommerce_schemas/facebook_opengraph/price' => 'scommerce_schemas/facebook_opengraph/price',
        'scommerce_schemas/facebook_opengraph/site_name' => 'scommerce_schemas/facebook_opengraph/site_name',
        'scommerce_schemas/facebook_opengraph/app_id' => 'scommerce_schemas/facebook_opengraph/app_id',
        'scommerce_schemas/pinterest_rich_pins/enable' => 'scommerce_schemas/pinterest_rich_pins/enable'
    );

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;
    
    /**
     * __construct
     * 
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context, 
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->_resource = $resource;
        parent::__construct($context);
    }

    /**
     * update config
     */
    public function updateConfig() {
        $this->updateCanonicalConfig($this->_canonicalCongfigData);
        $this->updateCanonicalConfig($this->_catalogConfigData);
        $this->updateCanonicalConfig($this->_richSnippetConfigData);
    }

    /**
     * Update config for modules
     * 
     * @param array $congfigData
     */
    protected function updateCanonicalConfig($congfigData) { 
        
        $connection = $this->_resource->getConnection();
        
        try {
            $tableName = $this->_resource->getTableName('core_config_data');
            $connection->beginTransaction();
            
            foreach($congfigData as $key => $value) {
                $connection->update($tableName,
                    ['path' => $key], 
                    ['path = ?' => $value]);
            }
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
        }

    }
}
