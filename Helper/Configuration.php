<?php
namespace MageSuite\ProductBestsellersRanking\Helper;

class Configuration
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function isDailyCalculationEnabled()
    {
        return $this->scopeConfig->getValue('bestsellers/cron/enabled');
    }
}