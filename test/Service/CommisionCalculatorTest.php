<?php

declare(strict_types=1);

namespace Transaction\Tests\Service;

use PHPUnit\Framework\TestCase;
use Transaction\Service\CommisionCalculator;

class CommisionCalculatorTest extends TestCase
{
    /**
     * @var CommisionCalculator
     */
    private $commisionCalculator, $expectationArray, $outputArray;

    public function setUp(): void
    {
        $this->commisionCalculator = new CommisionCalculator();
        // $this->expectationArray = array();
    }

    /**
     * @param array $inputArray
     * @param float $expectation
     *
     */
    public function testCalculator()
    {
        $inputPath = "./input.csv";
        $expectationPath = "./expectation.csv";
        $outputArray = array();
        $expectationArray = array();

        $inputFile = fopen($inputPath, "r");
        while (!feof($inputFile)) {
            $inputArray = fgetcsv($inputFile);
            $commision = $this->commisionCalculator->runCommand($inputArray);
            array_push($outputArray, $commision); 
        }
        fclose($inputFile);

        $expectationFile = fopen($expectationPath, "r");
        while (!feof($expectationFile)) {
            $expectationValue = fgetcsv($expectationFile);
            array_push( $expectationArray, $expectationValue[0]);
        }
        fclose($expectationFile);

        // remove comma from number before comparing
        $expectationArray = array_map(function($val) {
            return str_replace(',', '', $val);
        }, $expectationArray);
        
        $outputArray = array_map(function($val) {
            return str_replace(',', '', $val);
        }, $outputArray);

        $this->assertEquals(
            $expectationArray,
            $outputArray
        );
    }
}
