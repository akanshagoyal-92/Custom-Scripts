<?php
require __DIR__ . '/../app/bootstrap.php';

ini_set('max_execution_time', 18000);
ini_set('memory_limit', '8000M');
use Magento\Framework\App\Bootstrap;

$bootstrap 		= 	Bootstrap::create(BP, $_SERVER);
$objectManager 	= 	$bootstrap->getObjectManager();
$objectManager->get('Magento\Framework\Registry')->register('isSecureArea', true);

$customerFactory 	= 	$objectManager->create(\Magento\Customer\Model\CustomerFactory::class);
$customers 			= 	$customerFactory->create()->getCollection()
    					->addAttributeToSelect('*')
    					->addAttributeToFilter(
	                        array(
								array(
									'attribute' => 'email',
									'like' => '%qq.com%'
								),
								array(
									'attribute' => 'email',
									'like' => '%pp.com%'
								),
								array(
									'attribute' => 'email',
									'like' => '%126.com%'
								),
								array(
									'attribute' => 'email',
									'like' => '%139.com%'
								),
								array(
									'attribute' => 'email',
									'like' => '%189.com%'
								),
								array(
									'attribute' => 'email',
									'like' => '%@sina.com%'
								),
								array(
									'attribute' => 'email',
									'like' => '%foxmail.com%'
								),
								array(
									'attribute' => 'email',
									'like' => '%163.com%'
								),
								array(
									'attribute' => 'firstname',
									'like' => '%88lб%'
								),
								array(
									'attribute' => 'firstname',
									'like' => '%88l6%'
								),
								array(
									'attribute' => 'lastname',
									'like' => '%13670ll7063%'
								),
								array(
									'attribute' => 'lastname',
									'like' => '%2129417158%'
								),
								array(
									'attribute' => 'lastname',
									'like' => '%86O%'
								)
							)
	                    );
foreach($customers as $customer) {
	$customer->delete();
	echo "Found and deleted Customer: {$customer->getId()} :: {$customer->getEmail()}",PHP_EOL;
}