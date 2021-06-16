<?php
namespace MageSuite\ProductBestsellersRanking\Cron;

class ScoreCalculation
{
    /**
     * @var \MageSuite\ProductBestsellersRanking\Service\ScoreManager
     */
    protected $scoreManager;

    /**
     * @var \MageSuite\ProductBestsellersRanking\Helper\Configuration
     */
    protected $configuration;

    /**
     * ScoreCalculation constructor.
     * @param \MageSuite\ProductBestsellersRanking\Service\ScoreManager $scoreManager
     * @param \MageSuite\ProductBestsellersRanking\Helper\Configuration $configuration
     */
    public function __construct(
        \MageSuite\ProductBestsellersRanking\Service\ScoreManager $scoreManager,
        \MageSuite\ProductBestsellersRanking\Helper\Configuration $configuration
    ) {
        $this->scoreManager = $scoreManager;
        $this->configuration = $configuration;
    }

    public function execute()
    {
        if (!$this->configuration->isDailyCalculationEnabled()){
            return false;
        }

        $this->scoreManager->recalculateScores();

        return true;
    }
}
