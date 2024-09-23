<?php
@ini_set('memory_limit', '10240M');
@ini_set('max_execution_time', 86400);
ini_set('display_errors', 1);
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$url = \Magento\Framework\App\ObjectManager::getInstance();
$storeManager = $url->get('\Magento\Store\Model\StoreManagerInterface');
$state = $objectManager->get('\Magento\Framework\App\State');
$state->setAreaCode('frontend');

// Get website id
$website_id = $storeManager->getWebsite()->getWebsiteId();
$store = $storeManager->getStore();
$store_id = $store->getStoreId();

$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
$connection = $resource->getConnection();

$error        = [];
$success      = [];
$headerColumn = [];
$data      = [];
$header = null;

$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/customer-import.log');
$logger = new \Zend\Log\Logger();
$logger->addWriter($writer);

$filename = isset($argv[1]) ? $argv[1] : 'ig.madcowToMagentoAccountMapping.csv';
$row = 1;

if (($handle = fopen(__DIR__ . "/csv/" . $filename, "r")) !== FALSE)
{

    while (($data = fgetcsv($handle, 2000000, ",")) !== FALSE) {

        if ($row == 1) {
            $header = $data;
            $row++;
            continue;
        }

        $csvData   = array();
        $csvData   = array_combine($header, $data);
        echo "Row: " . $row . "....";
        try
        {

            // set customer fields from csv
            $email_address  = isset($csvData['email']) ? $csvData['email'] : null;
            $firstname      = isset($csvData['firstname']) ? $csvData['firstname'] : null;
            $lastname       = isset($csvData['lastname']) ? $csvData['lastname'] : null;

            // create or update customer
            $customer_factory   = $objectManager->get('\Magento\Customer\Model\CustomerFactory');
            $customer_data      = $customer_factory->create();
            $customer_data->setWebsiteId($website_id);
            $customer_data->loadByEmail($email_address);

            if(!$customer_data->getId()) { 

                $defaultPassword    =   'Cpap@123#';

                // create new customer
                $customer = $objectManager->get('\Magento\Customer\Model\CustomerFactory')->create();
                $customer->setWebsiteId($website_id);
                $customer->setEmail($email_address);
                $customer->setFirstname($firstname);
                $customer->setLastname($lastname);
                $customer->setGroupId(getGroupName($csvData['group']));
                $customer->setPassword($defaultPassword);
                $customer->save();

                echo "Customer Created: " . $email_address . "\n";

            } else { 

                // update customer
                $customer  =   $objectManager->get('\Magento\Customer\Model\CustomerFactory')->create()->load($customer_data->getId());
                $customer->setWebsiteId($website_id);
                $customer->setFirstname($firstname);
                $customer->setLastname($lastname);
                $customer->setGroupId(getGroupName($csvData['group']));
                $customer->save();

                echo "Customer Updated: " . $email_address . "\n";

            }

            if($csvData['is_subscribed']){
                $subscribed     =   subscribeCustomer($email_address, $row);
            }

        } catch (\Exception $e) {

            echo "Error: " . $e->getMessage() . "\n";
            $logger->info("Script => Customer Import, Row => {$row}, Email => {$email_address}, Error => {$e->getMessage()}");

        }

        $row++;

    }

    fclose($handle);

}

function getGroupName($group_name) {

    $group_id           =   1; // default group ID
    $bootstrap          =   Bootstrap::create(BP, $_SERVER);
    $objectManager      =   $bootstrap->getObjectManager();    
    $groupRepository    =   $objectManager->create('Magento\Customer\Model\ResourceModel\Group\Collection');
    $groupData          =   $groupRepository->addFieldToFilter('customer_group_code',['eq'=>$group_name]);
    
    if(!empty($groupData) && $groupData->getSize() > 0){

        $group          =   $groupData->getFirstItem();
        if (!empty($group->getCustomerGroupId())) {
            $group_id = $group->getCustomerGroupId();
        }

    }

    echo "Group ID: " . $group_id . "... ";
    return $group_id;    
    
}

function subscribeCustomer($email, $row){
    $bootstrap          =   Bootstrap::create(BP, $_SERVER);
    $objectManager      =   $bootstrap->getObjectManager();
    $state              =   $objectManager->get('\Magento\Framework\App\State');
    $state->setAreaCode('frontend');

    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/customer-import.log');
    $logger = new \Zend\Log\Logger();
    $logger->addWriter($writer);    

    try{
        $subscriptionFact   =   $objectManager->get('Magento\Newsletter\Model\SubscriberFactory');
        $subscriptionFact->create()->subscribe($email); 
        return true;   
    } catch(\Exception $e) {
        echo "Error in subscription : " . $e->getMessage() . "... ";
        $logger->info("Script => Customer Import (newsletter subscription), Row => {$row}, Email => {$email}, Error => {$e->getMessage()}");
        return false;
    }    
}
