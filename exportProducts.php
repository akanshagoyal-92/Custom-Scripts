<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '5G');
error_reporting(E_ALL);

use Magento\Framework\App\Bootstrap;
require realpath(__DIR__) . '/../app/bootstrap.php';

$bootstrap      =   Bootstrap::create(BP, $_SERVER);
$objectManager  =   $bootstrap->getObjectManager();
$state          =   $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');
$registry       =   $objectManager->get('Magento\Framework\Registry');
$registry->register('isSecureArea', true);
$fp                 =   fopen(realpath(__DIR__) ."/exported-products.csv","w+");
$objectManager      =   \Magento\Framework\App\ObjectManager::getInstance();
$productCollection  =   $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection');

$collection = $productCollection->addAttributeToSelect('*')->addFieldTofilter('type_id','simple')
        ->addFieldToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)->load();

$data   =   array('categories', 'name', 'description', 'url_key', 'meta_title', 'meta_keyword', 'meta_description', 'image');

fputcsv($fp, $data); 

try{
    foreach ($collection as $product){
        $catPath        =   array();
        $categories     =   '';
        $categoryIds    =   $product->getCategoryIds();
        if(!empty($categoryIds)){
            foreach ($categoryIds as $categoryIdsKey => $categoryIdsValue) {
                $category   =   $objectManager->create('Magento\Catalog\Model\Category')->load($categoryIdsValue);
                $catPath[]  =   $category->getUrlPath();
            }
            $categories     =   implode(',', $catPath);
        }

        $data = array();
        $data[] = $categories; 
        $data[] = $product->getName(); 
        $data[] = $product->getDescription();  
        $data[] = $product->getUrlKey();      
        $data[] = $product->getMetaTitle();  
        $data[] = $product->getMetaKeyword();  
        $data[] = $product->getMetaDescription();  
        if($product->getImage() == 'no_selection'){
            $data[] = '';  
        }else{
            $data[] = $product->getImage();  
        }
        fputcsv($fp, $data); 
    } 

    fclose($fp);
} catch(\Exception $e) {
    echo $e->getMessage();
}