<?php

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\ObjectManager;

require __DIR__ . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
/** @var \Magento\Framework\App\Http $app */
$app = $bootstrap->createApplication('Magento\Framework\App\Http');

$objectManager = ObjectManager::getInstance(); // Instance of object manager
$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
$connection = $resource->getConnection();

umask(0);
if ( !empty($_GET['deleteSkus'])) {
	if (strtolower($_GET['deleteSkus']) == 'all') {
		$_deleteSkus = 'all';
		echo 'Full primary category update Mode. All primary categories will be updated<br/>';
	} else {
		$_deleteSkus = $_GET['deleteSkus'];
		echo 'Update for single SKU Mode<br/>';
	}
} else {
	$_deleteSkus = false;
	echo 'No delete parameters found. Insert primary category Mode. <br/>';
}

/**
 * Retrieve the read connection
 */
$readConnection = $resource->getConnection('core_read');

/**
 * Retrieve the write connection
 */
$writeConnection = $resource->getConnection('core_write');

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
	echo $e->getMessage();
}
$query = "select attribute_id from ".$tablePrefix."eav_attribute where attribute_code='" . $field ."' limit 1";
try{
	$attribute_id = $readConnection->fetchOne($query);
}
catch (Exception $e){
	echo $e->getMessage();
}

$query = "SELECT e.attribute_id FROM eav_attribute e LEFT JOIN eav_entity_type t ON e.entity_type_id = t.entity_type_id WHERE e.attribute_code = 'exclude_from_primary_category' AND t.entity_type_code = 'catalog_category'";
try {
	$exclude_id = $readConnection->fetchOne($query);
} catch (Exception $e) {
	echo $e->getMessage();
}
$query = "SELECT e.attribute_id FROM eav_attribute e LEFT JOIN eav_entity_type t ON e.entity_type_id = t.entity_type_id WHERE e.attribute_code = 'primary_category_priority' AND t.entity_type_code = 'catalog_category'";
try {
	$priority_id = $readConnection->fetchOne($query);
} catch (Exception $e) {
	echo $e->getMessage();
}

$exclude_query =
	"SELECT DISTINCT main_table.entity_id FROM " . $tablePrefix . "catalog_category_entity main_table
        LEFT JOIN " . $tablePrefix . "catalog_category_entity_int at_path ON at_path.entity_id=main_table.entity_id
        AND at_path.attribute_id = " . $exclude_id . " WHERE at_path.`value` = 1 AND at_path.store_id = 0";

$category_priority_query =
	"SELECT DISTINCT main_table.entity_id, at_path.`value` FROM " . $tablePrefix . "catalog_category_entity main_table
            LEFT JOIN " . $tablePrefix . "catalog_category_entity_int at_path ON at_path.entity_id=main_table.entity_id
            AND at_path.attribute_id = " . $priority_id . " WHERE at_path.store_id = 0";


if ($_deleteSkus != false) {
	if ($_deleteSkus == 'all') {
		$query = 'delete from '.$tablePrefix.'catalog_product_entity_int where attribute_id='.$attribute_id.' and store_id=0';
		$writeConnection->exec($query);
	} else {
		$query = 'select entity_id from '.$tablePrefix.'catalog_product_entity where sku=\'' . $_deleteSkus . '\'';
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
		echo $e->getMessage().'<br/><br/>';
		echo $query;
		exit;
	}
	if ($totalRows>0){
		echo 'You have '.$totalRows.' more product(s) with primary category now';
	}
	else{
		echo 'It looks like all your products have primary category set';
	}
}
else{
	echo 'Sorry, nothing to create please check entity_type_id and attribute_id';
}



?>
