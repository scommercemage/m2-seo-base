<?php

namespace Scommerce\SeoBase\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Scommerce\SeoBase\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;


class ProductDataUpgrade implements DataPatchInterface
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
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ScopeConfigInterface     $scopeConfig,
        Config                   $config,
        Data                     $helper,
        EavSetupFactory          $eavSetupFactory
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->config = $config;
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return ProductDataUpgrade|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function apply()
    {
        if (!$this->helper->getLicenseKey(self::SEOBASE_LICENSE_KEY)) {
            $this->copyLicenseKey();
        }

        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $attributeCode = 'product_primary_category';
        $groupName = 'Search Engine Optimization';
        $entityType = $eavSetup->getEntityTypeId(Product::ENTITY);
        $eavSetup->updateAttribute($entityType, $attributeCode, 'source_model', 'Scommerce\SeoBase\Model\Entity\Attribute\Source\Categories');
        $eavSetup->updateAttribute($entityType, $attributeCode, 'group', $groupName);
        $eavSetup->updateAttribute($entityType, $attributeCode, 'global', \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE);

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
        return '2.0.12';
    }
}