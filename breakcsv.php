<?php
@ini_set('memory_limit', '1024M');
ini_set('display_errors', 1);
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$obj = $bootstrap->getObjectManager();

$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
$connection = $resource->getConnection();

$inputFile = 'partfinder_selector.csv';
$outputFile = 'output';

$splitSize = 50000;

$in = fopen($inputFile, 'r');

$rowCount = 0;
$fileCount = 1;
while (!feof($in)) {
    if (($rowCount % $splitSize) == 0) {
        if ($rowCount > 0) {
            fclose($out);
        }
        $out = fopen($outputFile . $fileCount++ . '.csv', 'w');
    }
    $data = fgetcsv($in);
    if ($data)
        fputcsv($out, $data);
    $rowCount++;
}

fclose($out);