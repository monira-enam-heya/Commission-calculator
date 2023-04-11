<?php

namespace Transaction;

require __DIR__ . '/vendor/autoload.php';

use Transaction\Service\CommisionCalculator;

$commisionCalculator = new CommisionCalculator();

$inputPath = readline('Enter input file path: ');
$inputFile = fopen($inputPath, "r");
while (!feof($inputFile)) {
    $inputArray = fgetcsv($inputFile);
    $commisionCalculator->runCommand($inputArray);
}
fclose($inputFile);
