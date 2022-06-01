<?php

namespace Scommerce\SeoBase\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Scommerce\SeoBase\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;


class ProductDataUpgrade2 implements DataPatchInterface
{

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var CategorySetupFactory
     */
    private $categorySetupFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var array
     */
    protected $configData = array();

    /**
     * @var array
     */
    protected $_licenseKey = array('scommerce_canonical/general/license_key',
        'scommerce_url/general/license_key',
        'scommerce_google_cards/general/license_key');

    /**
     * @const config path
     */
    const SEOBASE_LICENSE_KEY = 'scommerce_seobase/general/license_key';


    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $config
     * @param Data $helper
     * @param CategorySetupFactory $categorySetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ScopeConfigInterface     $scopeConfig,
        Config                   $config,
        Data                     $helper,
        CategorySetupFactory $categorySetupFactory
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->config = $config;
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
    }

    public function apply()
    {
        if (!$this->helper->getLicenseKey(self::SEOBASE_LICENSE_KEY)) {
            $this->copyLicenseKey();
        }

        $this->moduleDataSetup->getConnection()->startSetup();

        $categorySetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);

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
        $this->moduleDataSetup->getConnection()->endSetup();
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
        return [
            ProductDataInstall::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.0.11';
    }
}
