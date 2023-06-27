<?php

namespace MageSuite\ProductBestsellersRanking\DataProviders;

class BoostingFactorDataProvider
{
    protected $boostingFactorsConfig = [];

    protected \MageSuite\ProductBestsellersRanking\Helper\Configuration $configuration;

    public function __construct(\MageSuite\ProductBestsellersRanking\Helper\Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function setBoostingFactors($boostingFactorsArray)
    {
        $this->boostingFactorsConfig = $boostingFactorsArray;

        return $this;
    }

    public function getBoostingFactors()
    {
        if (!empty($this->boostingFactorsConfig)) {
            return $this->boostingFactorsConfig;
        }

        return [
            'boosterA' =>
                [
                    'value' => $this->configuration->getBoostingFactorWeek(),
                    'max_days_old' => 7
                ],
            'boosterB' =>
                [
                    'value' => $this->configuration->getBoostingFactorMonth(),
                    'max_days_old' => 30
                ],
            'boosterC' =>
                [
                    'value' => $this->configuration->getBoostingFactorYear(),
                    'max_days_old' => 365
                ],
            'boosterD' =>
                [
                    'value' => $this->configuration->getBoostingFactorGeneral(),
                    'max_days_old' => 99999
                ]
        ];
    }
}
