<?php

declare(strict_types=1);

namespace MageSuite\ProductBestsellersRanking\Helper;

class Configuration
{
    protected ?int $scoreMultiplierAttributeId = null;
    protected ?float $soldOutFactor = null;

    public const XML_PATH_BESTSELLERS_CRON_ENABLED = 'bestsellers/cron/enabled';
    public const XML_PATH_BESTSELLERS_CRON_USE_TRANSACTIONS = 'bestsellers/cron/use_transactions';
    public const XML_PATH_BESTSELLERS_CRON_CRON_CRASH_DETECTOR_ENABLED = 'bestsellers/cron/cron_crash_detector_enabled';

    public const XML_PATH_BESTSELLERS_PERFORMANCE_BATCH_SIZE = 'bestsellers/performance/batch_size';
    public const XML_PATH_BESTSELLERS_SORTING_DIRECTION = 'bestsellers/sorting/direction';
    public const XML_PATH_BESTSELLERS_ORDERS_PERIOD = 'bestsellers/orders_period/period';

    public const XML_PATH_BESTSELLERS_BOOSTING_FACTORS_BOOSTING_FACTOR_SOLD_OUT = 'bestsellers/boosting_factors/boosting_factor_sold_out';
    public const XML_PATH_BESTSELLERS_BOOSTING_FACTORS_BOOSTING_FACTOR_WEEK = 'bestsellers/boosting_factors/boosting_factor_week';
    public const XML_PATH_BESTSELLERS_BOOSTING_FACTORS_BOOSTING_FACTOR_MONTH = 'bestsellers/boosting_factors/boosting_factor_month';
    public const XML_PATH_BESTSELLERS_BOOSTING_FACTORS_BOOSTING_FACTOR_YEAR = 'bestsellers/boosting_factors/boosting_factor_year';
    public const XML_PATH_BESTSELLERS_BOOSTING_FACTORS_BOOSTING_FACTOR_GENERAL = 'bestsellers/boosting_factors/boosting_factor_general';
    public const XML_PATH_BESTSELLERS_BOOSTING_FACTORS_TURNOVER_MULTIPLIER = 'bestsellers/boosting_factors/turnover_multiplier';

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
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_BESTSELLERS_CRON_ENABLED);
    }

    public function isUseTransactionsEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_BESTSELLERS_CRON_USE_TRANSACTIONS);
    }

    public function isCronCrashDetectorEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_BESTSELLERS_CRON_CRON_CRASH_DETECTOR_ENABLED);
    }

    public function getBatchSize(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_BESTSELLERS_PERFORMANCE_BATCH_SIZE);
    }

    public function getScoreMultiplierAttributeId(): int
    {
        if ($this->scoreMultiplierAttributeId === null) {
            $this->scoreMultiplierAttributeId = (int)$this->eavAttribute->getIdByCode(\Magento\Catalog\Model\Product::ENTITY, 'bestseller_score_multiplier');
        }

        return $this->scoreMultiplierAttributeId;
    }

    public function getSortOrder(): ?string
    {
        return $this->scopeConfig->getValue('bestsellers/sorting/direction', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getOrdersPeriod(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_BESTSELLERS_ORDERS_PERIOD);
    }

    public function getBoostingFactorWeek(): float
    {
        return (float)$this->scopeConfig->getValue(self::XML_PATH_BESTSELLERS_BOOSTING_FACTORS_BOOSTING_FACTOR_WEEK);
    }

    public function getBoostingFactorMonth(): float
    {
        return (float)$this->scopeConfig->getValue(self::XML_PATH_BESTSELLERS_BOOSTING_FACTORS_BOOSTING_FACTOR_MONTH);
    }

    public function getBoostingFactorYear(): float
    {
        return (float)$this->scopeConfig->getValue(self::XML_PATH_BESTSELLERS_BOOSTING_FACTORS_BOOSTING_FACTOR_YEAR);
    }

    public function getBoostingFactorGeneral(): float
    {
        return (float)$this->scopeConfig->getValue(self::XML_PATH_BESTSELLERS_BOOSTING_FACTORS_BOOSTING_FACTOR_GENERAL);
    }

    public function getSoldOutFactor(): float
    {
        if ($this->soldOutFactor === null) {
            return (float)$this->scopeConfig->getValue(self::XML_PATH_BESTSELLERS_BOOSTING_FACTORS_BOOSTING_FACTOR_SOLD_OUT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }

        return $this->soldOutFactor;
    }

    public function getTurnoverMultiplier(): float
    {
        return (float)$this->scopeConfig->getValue(self::XML_PATH_BESTSELLERS_BOOSTING_FACTORS_TURNOVER_MULTIPLIER);
    }
}
