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
    protected $_catalogConfigData = array(
        'scommerce_seobase/general/exclude_categories' => 'scommerce_url/general/exclude_categories');
     
    /**
     * @var array 
     */
    protected $_richSnippetConfigData = array(
        'scommerce_google_cards/twitter_cards/enable' => 'scommerce_schemas/twitter_cards/enable',
        'scommerce_google_cards/twitter_cards/description' => 'scommerce_schemas/twitter_cards/description',
        'scommerce_google_cards/twitter_cards/card_type' => 'scommerce_schemas/twitter_cards/card_type',
        'scommerce_google_cards/twitter_cards/price' => 'scommerce_schemas/twitter_cards/price',
        'scommerce_google_cards/twitter_cards/site' => 'scommerce_schemas/twitter_cards/site',
        'scommerce_google_cards/twitter_cards/creator' => 'scommerce_schemas/twitter_cards/creator',
        'scommerce_google_cards/facebook_opengraph/enable' => 'scommerce_schemas/facebook_opengraph/enable',
        'scommerce_google_cards/facebook_opengraph/description' => 'scommerce_schemas/facebook_opengraph/description',
        'scommerce_google_cards/facebook_opengraph/price' => 'scommerce_schemas/facebook_opengraph/price',
        'scommerce_google_cards/facebook_opengraph/site_name' => 'scommerce_schemas/facebook_opengraph/site_name',
        'scommerce_google_cards/facebook_opengraph/app_id' => 'scommerce_schemas/facebook_opengraph/app_id',
        'scommerce_google_cards/pinterest_rich_pins/enable' => 'scommerce_schemas/pinterest_rich_pins/enable'
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
