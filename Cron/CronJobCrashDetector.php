<?php

namespace MageSuite\ProductBestsellersRanking\Cron;

class CronJobCrashDetector
{
    protected \MageSuite\ProductBestsellersRanking\Service\CronJobCrashDetector $cronJobCrashDetector;
    protected \MageSuite\ProductBestsellersRanking\Helper\Configuration $configuration;

    public function __construct(
        \MageSuite\ProductBestsellersRanking\Service\CronJobCrashDetector $cronJobCrashDetector,
        \MageSuite\ProductBestsellersRanking\Helper\Configuration $configuration
    ) {
        $this->cronJobCrashDetector = $cronJobCrashDetector;
        $this->configuration = $configuration;
    }

    public function execute()
    {
        if (!$this->configuration->isCronCrashDetectorEnabled()) {
            return;
        }

        return $this->cronJobCrashDetector->execute();
    }
}
