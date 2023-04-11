<?php

namespace Transaction\Service;

class CommisionCalculator
{
    public $timeline;
    public $sum;
    public $commision;
    public $week_matched;
    public $operation_count;
    public $free_treshhold;
    public $base_currency;
    public $currency_api;
    public $json;
    public $threshold_crossed;

    public function __construct()
    {
        $this->timeline = [];
        $this->free_treshhold = 1000.00;
        $this->base_currency = 'EUR';
        $this->currency_api = 'https://developers.paysera.com/tasks/api/currency-exchange-rates';
        $json = file_get_contents($this->currency_api);
        $this->json = json_decode($json);
        $this->week_matched = 0;
        $this->operation_count = 0;
        $this->sum = 0;
        $this->threshold_crossed = false;
    }

    public function depositeCommision(float $amount)
    {
        $commision = ($amount * (0.03 / 100));

        return $commision;
    }

    public function withdrawCommision(string $date, int $uid, string $utype, string $otype, int $amount, string $currency)
    {
        if ($utype == 'private') {
            // Check if there was operation on same week
            if (!array_key_exists($uid, $this->timeline)) {
                $this->timeline[$uid] = $date;
                $this->operation_count = 1;
                $this->week_matched = 0;
                $this->sum = 0;
                $this->threshold_crossed = 0;
            } else {
                $stored_week = date('W', $this->timeline[$uid]);
                $stored_year = date('Y', $this->timeline[$uid]);
                $current_week = date('W', $date);
                $current_year = date('Y', $date);

                if (($stored_year < $current_year && ($current_year - $stored_year) == 1 && $stored_week == $current_week)
                    || ($stored_year == $current_year && $stored_week == $current_week)
                ) {
                    $this->week_matched = 1;
                    ++$this->operation_count;
                } else {
                    $this->week_matched = 0;
                    $this->timeline[$uid] = $date;
                    $this->sum = 0;
                    $this->operation_count = 0;
                    $this->threshold_crossed = 0;
                }
            }

            if ($this->operation_count > 3) {
                // applies rule 1 for private withdraw
                $commision = ($amount * (0.3 / 100));
            } else {
                // applies rule 2 for private withdraw
                if ($currency != $this->base_currency) {
                    $rate = $this->json->rates->$currency;
                    $base_amount = ($amount / $rate) + $this->sum;
                } else {
                    $base_amount = $amount + $this->sum;
                }

                if ($this->threshold_crossed == 1) {
                    $commision = ($base_amount * (0.3 / 100));
                } elseif ($base_amount > $this->free_treshhold) {
                    $exceeded_amount = $base_amount - $this->free_treshhold;
                    $commision = ($exceeded_amount * (0.3 / 100));

                    if ($currency != $this->base_currency && isset($rate)) {
                        $commision = $commision * $rate;
                    }
                    $this->threshold_crossed = 1;
                    $this->sum = 0;
                } else {
                    if ($this->free_treshhold > $base_amount) {
                        $this->sum += $base_amount;
                    }
                    $commision = 0;
                }
            }

            return $commision;
        } elseif ($utype == 'business') {
            $commision = ($amount * (0.5 / 100));

            return $commision;
        }
    }

    public function runCommand(array $inputArray)
    {
        $date = strtotime($inputArray[0]);
        $uid = $inputArray[1];
        $utype = $inputArray[2];
        $otype = $inputArray[3];
        $amount = $inputArray[4];
        $currency = $inputArray[5];

        if ($otype == 'deposit') {
            $commision = number_format(round($this->depositeCommision($amount), 2), 2);
            echo $commision.PHP_EOL;
        } elseif ($otype == 'withdraw') {
            $commision = number_format(round($this->withdrawCommision($date, $uid, $utype, $otype, $amount, $currency), 2), 2);
            echo $commision.PHP_EOL;
        }

        return $commision;
    }
}
