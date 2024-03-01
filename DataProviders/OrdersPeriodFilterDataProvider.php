<?php

namespace MageSuite\ProductBestsellersRanking\DataProviders;

class OrdersPeriodFilterDataProvider
{
    public const PERIOD_LAST_WEEK = 1;
    public const PERIOD_LAST_MONTH = 2;
    public const PERIOD_LAST_YEAR = 3;

    protected \MageSuite\ProductBestsellersRanking\Helper\Configuration $configuration;

    public function __construct(\MageSuite\ProductBestsellersRanking\Helper\Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getOrdersPeriodFilter()
    {
        $periodConfig = $this->configuration->getOrdersPeriod();

        switch ($periodConfig) {
            case self::PERIOD_LAST_WEEK:
                return date('Y-m-d 00:00:00', strtotime('-7 days'));
            case self::PERIOD_LAST_MONTH:
                return date('Y-m-d 00:00:00', strtotime('-30 days'));
            case self::PERIOD_LAST_YEAR:
                return date('Y-m-d 00:00:00', strtotime('-365 days'));
            default:
                return null;
        }
    }
}
