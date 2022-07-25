<?php

namespace MageSuite\ProductBestsellersRanking\Test\Integration\Model;

abstract class AbstractCalculationsTest extends \PHPUnit\Framework\TestCase
{
    protected function getBoostingFactorArray()
    {
        return [
            'boosterA' =>
                [
                    'value' => 3,
                    'max_days_old' => 7
                ],
            'boosterB' =>
                [
                    'value' => 2,
                    'max_days_old' => 30
                ],
            'boosterC' =>
                [
                    'value' => 1,
                    'max_days_old' => 365
                ],
            'boosterD' =>
                [
                    'value' => 0,
                    'max_days_old' => 999999999
                ]
        ];
    }
}
