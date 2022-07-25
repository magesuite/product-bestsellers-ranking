<?php
namespace MageSuite\ProductBestsellersRanking\Cron;

class ScoreCalculation
{
    protected \MageSuite\ProductBestsellersRanking\Service\ScoreManager $scoreManager;
    protected \MageSuite\ProductBestsellersRanking\Helper\Configuration $configuration;
    protected \Magento\Framework\Lock\LockManagerInterface $lockManager;

    public function __construct(
        \MageSuite\ProductBestsellersRanking\Service\ScoreManager $scoreManager,
        \MageSuite\ProductBestsellersRanking\Helper\Configuration $configuration,
        \Magento\Framework\Lock\LockManagerInterface $lockManager
    ) {
        $this->scoreManager = $scoreManager;
        $this->configuration = $configuration;
        $this->lockManager = $lockManager;
    }

    public function execute()
    {
        if (!$this->configuration->isDailyCalculationEnabled()) {
            return false;
        }

        $this->lockManager->lock(\MageSuite\ProductBestsellersRanking\Service\CronJobCrashDetector::LOCK_NAME, 5);

        $this->scoreManager->recalculateScores();

        return true;
    }
}
