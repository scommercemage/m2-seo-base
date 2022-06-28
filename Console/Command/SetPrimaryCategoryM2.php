<?php

namespace Scommerce\SeoBase\Console\Command;

class SetPrimaryCategoryM2 extends \Symfony\Component\Console\Command\Command
{
    const SKU = 'sku';

    protected $resource;

    protected function __construct(
        \Magento\Framework\App\ResourceConnection $resource
    )
    {
        parent::__construct();
        $this->resource = $resource;
    }

    protected function configure()
    {
        $this->setName('scommerce:set:category');
        $this->setDescription('Set primary category');
        $this->addOption(self::SKU,
            null,
            \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL,
            'SKUs');
        parent::configure();
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $connection = $this->resource->getConnection();
        $deleteSkus = $input->getOption(self::SKU);
        if ( !empty($deleteSkus)) {
            if (strtolower($deleteSkus) == 'all') {
                $deleteSkus = 'all';
                $output->writeln('Full primary category update Mode. All primary categories will be updated');
            } else {
                $output->writeln('Update for single SKU Mode');
            }
        } else {
            $deleteSkus = false;
            $output->writeln('No delete parameters found. Insert primary category Mode.');
        }
        /**
         * Retrieve the read connection
         */
        $readConnection = $this->resource->getConnection('core_read');
        /**
         * Retrieve the write connection
         */
        $writeConnection = $this->resource->getConnection('core_write');

        /**
         * Set the field name
         */
        $field = 'product_primary_category';

        /**
         * Set the entity type code
         */
        $entityTypeCode = 'catalog_product';
        $tablePrefix = '';
        $query = "select entity_type_id from ".$tablePrefix."eav_entity_type where entity_type_code='" . $entityTypeCode ."' limit 1";

        try{
            $entity_type_id = $readConnection->fetchOne($query);
        }
        catch (Exception $e){
            $output->writeln($e->getMessage());
        }
        $query = "select attribute_id from ".$tablePrefix."eav_attribute where attribute_code='" . $field ."' limit 1";
        try{
            $attribute_id = $readConnection->fetchOne($query);
        }
        catch (Exception $e){
            $output->writeln($e->getMessage());
        }

        $query = "SELECT e.attribute_id FROM eav_attribute e LEFT JOIN eav_entity_type t ON e.entity_type_id = t.entity_type_id WHERE e.attribute_code = 'exclude_from_primary_category' AND t.entity_type_code = 'catalog_category'";
        try {
            $exclude_id = $readConnection->fetchOne($query);
        } catch (Exception $e) {
            $output->writeln($e->getMessage());
        }
        $query = "SELECT e.attribute_id FROM eav_attribute e LEFT JOIN eav_entity_type t ON e.entity_type_id = t.entity_type_id WHERE e.attribute_code = 'primary_category_priority' AND t.entity_type_code = 'catalog_category'";
        try {
            $priority_id = $readConnection->fetchOne($query);
        } catch (Exception $e) {
            $output->writeln($e->getMessage());
        }

        $exclude_query =
            "SELECT DISTINCT main_table.entity_id FROM " . $tablePrefix . "catalog_category_entity main_table
        LEFT JOIN " . $tablePrefix . "catalog_category_entity_int at_path ON at_path.entity_id=main_table.entity_id
        AND at_path.attribute_id = " . $exclude_id . " WHERE at_path.`value` = 1 AND at_path.store_id = 0";

        $category_priority_query =
            "SELECT DISTINCT main_table.entity_id, at_path.`value` FROM " . $tablePrefix . "catalog_category_entity main_table
            LEFT JOIN " . $tablePrefix . "catalog_category_entity_int at_path ON at_path.entity_id=main_table.entity_id
            AND at_path.attribute_id = " . $priority_id . " WHERE at_path.store_id = 0";


        if ($deleteSkus != false) {
            if ($deleteSkus == 'all') {
                $query = 'delete from '.$tablePrefix.'catalog_product_entity_int where attribute_id='.$attribute_id.' and store_id=0';
                $writeConnection->exec($query);
            } else {
                $query = 'select entity_id from '.$tablePrefix.'catalog_product_entity where sku=\'' . $deleteSkus . '\'';
                $product_id = $readConnection->fetchOne($query);
                $query = 'delete from ' . $tablePrefix . 'catalog_product_entity_int where attribute_id=' . $attribute_id .
                    ' and store_id=0 and entity_id=' . $product_id;
                $writeConnection->exec($query);
            }
        }

        if (strlen($entity_type_id)>0 &&  strlen($attribute_id)>0){
            $query = 'insert into '.$tablePrefix.'catalog_product_entity_int
					select distinct null,'. $attribute_id.', 0,
					sub.product_id,sub.category_id from '.$tablePrefix.'catalog_category_product c
					inner join (select distinct category_id,product_id from (select distinct category_id,product_id
					from '.$tablePrefix.'catalog_category_product
					 left JOIN ( ' . $category_priority_query . ' ) AS cat_priority ON cat_priority.entity_id = category_id
					where category_id not in ( ' . $exclude_query . ') order by COALESCE(cat_priority.`value`, 0) DESC, category_id desc) cp group by product_id) sub
					on c.category_id=sub.category_id where sub.product_id not in
					(select entity_id from '.$tablePrefix.'catalog_product_entity_int
					where attribute_id='.$attribute_id.' and store_id=0)
					order by c.product_id, c.category_id desc';

            /**
             * Execute the query
             */
            try{
                $totalRows = $writeConnection->exec($query);
            }
            catch (Exception $e){
                $output->writeln($e->getMessage());
                $output->writeln($query);
                return;
            }
            if ($totalRows>0){
                $output->writeln('You have '.$totalRows.' more product(s) with primary category now');
            }
            else{
                $output->writeln('It looks like all your products have primary category set');
            }
        }
        else{
            $output->writeln('Sorry, nothing to create please check entity_type_id and attribute_id');
        }

//        $output->writeln($name);
    }
}
