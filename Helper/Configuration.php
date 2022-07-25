<?php

namespace MageSuite\ProductBestsellersRanking\Helper;

class Configuration
{
    protected $scoreMultiplierAttributeId = null;
    protected $soldOutFactor = null;

    protected \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig;
    protected \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->eavAttribute = $eavAttribute;
    }

    public function isDailyCalculationEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('bestsellers/cron/enabled');
    }

    public function isUseTransactionsEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('bestsellers/cron/use_transactions');
    }

    public function getBatchSize(): int
    {
        return (int)$this->scopeConfig->getValue('bestsellers/performance/batch_size');
    }

    public function getScoreMultiplierAttributeId(): int
    {
        if ($this->scoreMultiplierAttributeId === null) {
            $this->scoreMultiplierAttributeId = $this->eavAttribute->getIdByCode(
                \Magento\Catalog\Model\Product::ENTITY,
                'bestseller_score_multiplier'
            );
        }

        return $this->scoreMultiplierAttributeId;
    }

    public function getSoldOutFactor(): float
    {
        if ($this->soldOutFactor === null) {
            return (float)$this->scopeConfig->getValue('bestsellers/boosting_factors/boosting_factor_sold_out', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }

        return $this->soldOutFactor;
    }

    public function getSortOrder(): ?string
    {
        return $this->scopeConfig->getValue('bestsellers/sorting/direction', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getOrdersPeriod()
    {
        return $this->scopeConfig->getValue('bestsellers/orders_period/period');
    }

    public function getBoostingFactorWeek()
    {
        return $this->scopeConfig->getValue('bestsellers/boosting_factors/boosting_factor_week');
    }

    public function getBoostingFactorMonth()
    {
        return $this->scopeConfig->getValue('bestsellers/boosting_factors/boosting_factor_month');
    }

    public function getBoostingFactorYear()
    {
        return $this->scopeConfig->getValue('bestsellers/boosting_factors/boosting_factor_week');
    }

    public function getBoostingFactorGeneral()
    {
        return $this->scopeConfig->getValue('bestsellers/boosting_factors/boosting_factor_general');
    }

    public function isCronCrashDetectorEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('bestsellers/cron/cron_crash_detector_enabled');
    }
}
