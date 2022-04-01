<?php

namespace Scommerce\SeoBase\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetPrimaryCategory extends Command
{
    const DELETE_SKU = 'delete-sku';
    /**
     * @var \Magento\Framework\App\State
     */
    private $state;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * ProcessSubscriptionsCommand constructor.
     * @param \Magento\Framework\App\State $state
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        State $state,
        ResourceConnection $resourceConnection
    ) {
        $this->state = $state;
        $this->resourceConnection = $resourceConnection;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('scommerce:seo-base:set-primary-category')
            ->setDescription('Set primary category for products')
            ->addOption(
                self::DELETE_SKU,
                null,
                InputOption::VALUE_OPTIONAL,
                'Sku to delete also can be all'
            );
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->emulateAreaCode(
            \Magento\Framework\App\Area::AREA_CRONTAB,
            [$this, "executeCallBack"],
            [$input, $output]
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function executeCallBack(InputInterface $input, OutputInterface $output)
    {
        $deleteSkus = $input->getOption(self::DELETE_SKU);

        /**
         * Retrieve the read connection
         */
        $readConnection = $this->resourceConnection->getConnection('core_read');
        /**
         * Retrieve the write connection
         */
        $writeConnection = $this->resourceConnection->getConnection('core_write');
        /**
         * Set the field name
         */
        $field = 'product_primary_category';
        /**
         * Set the entity type code
         */
        $entityTypeCode = 'catalog_product';
        $eavEntityTypeTable = $readConnection->getTableName('eav_entity_type');
        $eavAttributeTable = $readConnection->getTableName('eav_attribute');
        $catalogCategoryEntityTable = $readConnection->getTableName('catalog_category_entity');
        $catalogCategoryEntityIntTable = $readConnection->getTableName('catalog_category_entity_int');
        $catalogProductEntityTable = $readConnection->getTableName('catalog_product_entity');
        $catalogProductEntityIntTable = $readConnection->getTableName('catalog_product_entity_int');
        $catalogCategoryProductTable = $readConnection->getTableName('catalog_category_product');

        $query = "select entity_type_id from $eavEntityTypeTable where entity_type_code='$entityTypeCode' limit 1";
        try {
            $entity_type_id = $readConnection->fetchOne($query);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return Cli::RETURN_FAILURE;
        }

        $query = "select attribute_id from $eavAttributeTable where attribute_code='$field' limit 1";
        try {
            $attribute_id = $readConnection->fetchOne($query);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return Cli::RETURN_FAILURE;
        }

        $query = "SELECT e.attribute_id FROM $eavAttributeTable e LEFT JOIN $eavEntityTypeTable t ON e.entity_type_id = t.entity_type_id WHERE e.attribute_code = 'exclude_from_primary_category' AND t.entity_type_code = 'catalog_category'";
        try {
            $exclude_id = $readConnection->fetchOne($query);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return Cli::RETURN_FAILURE;
        }

        $query = "SELECT e.attribute_id FROM $eavAttributeTable e LEFT JOIN $eavEntityTypeTable t ON e.entity_type_id = t.entity_type_id WHERE e.attribute_code = 'primary_category_priority' AND t.entity_type_code = 'catalog_category'";
        try {
            $priority_id = $readConnection->fetchOne($query);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return Cli::RETURN_FAILURE;
        }

        $exclude_query =
            "SELECT DISTINCT main_table.entity_id FROM $catalogCategoryEntityTable main_table
        LEFT JOIN $catalogCategoryEntityIntTable at_path ON at_path.entity_id=main_table.entity_id
        AND at_path.attribute_id = $exclude_id WHERE at_path.`value` = 1 AND at_path.store_id = 0";
        $category_priority_query =
            "SELECT DISTINCT main_table.entity_id, at_path.`value` FROM $catalogCategoryEntityTable main_table
            LEFT JOIN $catalogCategoryEntityIntTable at_path ON at_path.entity_id=main_table.entity_id
            AND at_path.attribute_id = $priority_id WHERE at_path.store_id = 0";

        if ($deleteSkus != false) {
            if ($deleteSkus == 'all') {
                $query = "delete from $catalogProductEntityIntTable where attribute_id=$attribute_id and store_id=0";
                $writeConnection->exec($query);
            } else {
                $query = "select entity_id from $catalogProductEntityTable where sku='$deleteSkus'";
                $product_id = $readConnection->fetchOne($query);
                $query = "delete from $catalogProductEntityIntTable where attribute_id=$attribute_id and store_id=0 and entity_id=$product_id";
                $writeConnection->exec($query);
            }
        }

        if (strlen($entity_type_id) > 0 && strlen($attribute_id) > 0) {
            $query = "insert into $catalogProductEntityIntTable
					select distinct null,$attribute_id, 0,
					sub.product_id,sub.category_id from $catalogCategoryProductTable c
					inner join (select distinct category_id,product_id from (select distinct category_id,product_id
					from $catalogCategoryProductTable left JOIN ($category_priority_query) AS cat_priority ON cat_priority.entity_id = category_id
					where category_id not in ($exclude_query) order by COALESCE(cat_priority.`value`, 0) DESC, category_id desc) cp group by product_id) sub
					on c.category_id=sub.category_id where sub.product_id not in
					(select entity_id from $catalogProductEntityIntTable
					where attribute_id=$attribute_id and store_id=0)
					order by c.product_id, c.category_id desc";

            /**
             * Execute the query
             */
            try {
                $totalRows = $writeConnection->exec($query);
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
                $output->writeln($query);
                return Cli::RETURN_FAILURE;
            }
            if ($totalRows > 0) {
                $output->writeln("You have $totalRows more product(s) with primary category now");
            } else {
                $output->writeln("It looks like all your products have primary category set");
            }
        } else {
            $output->writeln("Sorry, nothing to create please check entity_type_id and attribute_id");
        }
        return Cli::RETURN_SUCCESS;
    }
}
