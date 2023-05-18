<?php
use Magento\Framework\App\Bootstrap;

/* load bootstrap */
require __DIR__ . '/../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$resource       = $objectManager->get('Magento\Framework\App\ResourceConnection');
$connection     = $resource->getConnection();

$sql = "SELECT GROUP_CONCAT(catalog_product_option.option_id) as option_id, GROUP_CONCAT(catalog_product_option_title.option_title_id) as gp_option_title_id, product_id, catalog_product_option_title.title, count(title) as repeatation FROM `catalog_product_option` LEFT JOIN catalog_product_option_title on  catalog_product_option_title.option_id = catalog_product_option.option_id GROUP BY title,product_id HAVING repeatation > 1";
$results =	$connection->fetchAll($sql);

if (!empty($results)) {
	foreach ($results as $resultsKey => $resultsValue) {
		$optionIdArr = explode(",", $resultsValue['option_id']);
		sort($optionIdArr);
		unset($optionIdArr[0]);
		foreach ($optionIdArr as $optionIdArrKey => $optionIdArrValue) {
			$optionTypeIdStr 	=	'';
			$optionTypeIdRe 	=	$connection->fetchAll("SELECT option_type_id FROM catalog_product_option_type_value WHERE option_id = '{$optionIdArrValue}'");

			foreach ($optionTypeIdRe as $optionTypeIdReKey => $optionTypeIdReValue) {
				$optionTypeIdArr[] 	=	$optionTypeIdReValue['option_type_id'];				
			}

			if (!empty($optionTypeIdArr)) {
				$optionTypeIdStr 	= implode(',', $optionTypeIdArr);
			}

			$catalogProductOptionDelete 		= "DELETE FROM catalog_product_option WHERE option_id = '{$optionIdArrValue}'";
			$catalogProductOptionTitleDelete 	= "DELETE FROM catalog_product_option_title WHERE option_id = '{$optionIdArrValue}'";
			$catalogProductOptionTypeValDelete 	= "DELETE FROM catalog_product_option_type_value WHERE option_id = '{$optionIdArrValue}'";
			$catalogProductOptionTypeValDelete 	= "DELETE FROM catalog_product_option_type_price WHERE option_type_id IN ({$optionTypeIdStr})";
			$catalogProductOptionTypeValDelete 	= "DELETE FROM catalog_product_option_type_title WHERE option_type_id IN ({$optionTypeIdStr})";

			$connection->query($catalogProductOptionDelete);
			$connection->query($catalogProductOptionTitleDelete);
			$connection->query($catalogProductOptionTypeValDelete);
			$connection->query($catalogProductOptionTypeValDelete);
			$connection->query($catalogProductOptionTypeValDelete);

			echo "Deleted option_id {$optionIdArrValue}, option_type_id {$optionTypeIdStr}".PHP_EOL;

		}

		print_r($resultsValue); die;
	}
} else {
    echo "0 results";
}