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

$filename = isset($argv[1]) ? $argv[1] : 'ig.madcowToMagentoAccountAddressesMapping.csv';
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
            // set address fields from csv
            $emailAddress   = isset($csvData['email']) ? $csvData['email'] : null;
            $firstname      = isset($csvData['firstname']) ? $csvData['firstname'] : null;
            $lastname       = isset($csvData['lastname']) ? $csvData['lastname'] : null;
            $company        = isset($csvData['company']) ? $csvData['company'] : null;
            $street0        = isset($csvData['street.0']) ? $csvData['street.0'] : null;
            $street1        = isset($csvData['street.1']) ? $csvData['street.1'] : null;
            $city           = isset($csvData['city']) ? $csvData['city'] : null;
            $region              = isset($csvData['region']) ? $csvData['region'] : null;
            $regionCode          = isset($csvData['region_code']) ? $csvData['region_code'] : null;
            $postcode            = isset($csvData['postcode']) ? $csvData['postcode'] : null;
            $country             = isset($csvData['country_code']) ? $csvData['country_code'] : null;
            $telephone           = isset($csvData['telephone']) ? $csvData['telephone'] : null;
            $defaultShipping     = isset($csvData['default_shipping']) ? $csvData['default_shipping'] : null;

            $street         =   array(
                                    $street0,
                                    $street1
                                );

            // create or update customer
            $customer_factory   = $objectManager->get('\Magento\Customer\Model\CustomerFactory');
            $customerData       = $customer_factory->create();
            $customerData->setWebsiteId($website_id);
            $customerData->loadByEmail($emailAddress);

            if(!$customerData->getId()) { 
                $defaultPassword    =   'Cpap@123#';
                // create new customer
                $customer = $objectManager->get('\Magento\Customer\Model\CustomerFactory')->create();
                $customer->setWebsiteId($website_id);
                $customer->setEmail($emailAddress);
                $customer->setFirstname($firstname);
                $customer->setLastname($lastname);
                $customer->setPassword($defaultPassword);
                $customer->setGroupId(1);
                $customer->save();

                echo "Customer Created: " . $emailAddress . "...";

                $customerId     =   $customer->getId();

            } else {
                $customerId     =   $customerData->getId();
            }

            $address = $objectManager->get('\Magento\Customer\Model\AddressFactory')->create();
            $address->setCustomerId($customerId)
                  ->setFirstname($firstname)
                  ->setLastname($lastname)
                  ->setPostcode($postcode)
                  ->setCity($city)
                  ->setRegion($region)
                  ->setCompany($company)
                  ->setStreet($street)
                  ->setIsDefaultBilling(false)
                  ->setIsDefaultShipping($defaultShipping)
                  ->setSaveInAddressBook('1')
                  ->setCountryId($country)
                  ->setTelephone($telephone)
                  ;  

            $regionId     =   getRegionId($country, $regionCode, $row);     

            if(!empty($regionId)){
                $address->setRegionId($regionId);
            }

            $address->save();

            echo "Created address of customer: " . $emailAddress . "\n";

        } catch (\Exception $e) {

            echo "Error: " . $e->getMessage() . "\n";
            $logger->info("Script => Customer Address Import, Row => {$row}, Email => {$emailAddress}, Error => {$e->getMessage()}");

        }

        $row++;

    }

    fclose($handle);

}

function getRegionId($countryCode, $regionCode, $row){
    $bootstrap          =   Bootstrap::create(BP, $_SERVER);
    $objectManager      =   $bootstrap->getObjectManager(); 

    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/customer-import.log');
    $logger = new \Zend\Log\Logger();
    $logger->addWriter($writer);    

    try{
        $region             =   $objectManager->get('Magento\Directory\Model\RegionFactory')->create();
        $regionId           =   $region->loadByCode($regionCode, $countryCode)->getId();  
        return $regionId;     
    }catch(\Exception $e){
        echo "Error in finding region : {$e->getMessage()} ...";
        $logger->info("Script => Customer Address Import (Region Finding), Row => {$row}, Error => {$e->getMessage()}");
    }    
}