<?php
namespace MageSuite\ProductBestsellersRanking\Cron;

class ScoreCalculation
{
    /**
     * @var \MageSuite\ProductBestsellersRanking\Model\IndexerFactory
     */
    protected $indexerFactory;
    /**
     * @var \MageSuite\ProductBestsellersRanking\Helper\Configuration
     */
    protected $configuration;
    /**
     * @var \MageSuite\ProductBestsellersRanking\Model\ScoreCalculationFactory
     */
    protected $scoreCalculationFactory;
    /**
     * @var \MageSuite\ProductBestsellersRanking\Model\ClearDailyScoreFactory
     */
    protected $clearDailyScoreFactory;

    /**
     * ScoreCalculation constructor.
     *
     * @param \MageSuite\ProductBestsellersRanking\Model\ScoreCalculationFactory $scoreCalculationFactory
     * @param \MageSuite\ProductBestsellersRanking\Model\ClearDailyScoreFactory $clearDailyScoreFactory
     * @param \MageSuite\ProductBestsellersRanking\Model\IndexerFactory $indexerFactory
     * @param \MageSuite\ProductBestsellersRanking\Helper\Configuration $configuration
     */
    public function __construct(
        \MageSuite\ProductBestsellersRanking\Model\ScoreCalculationFactory $scoreCalculationFactory,
        \MageSuite\ProductBestsellersRanking\Model\ClearDailyScoreFactory $clearDailyScoreFactory,
        \MageSuite\ProductBestsellersRanking\Model\IndexerFactory $indexerFactory,
        \MageSuite\ProductBestsellersRanking\Helper\Configuration $configuration
    )
    {
        $this->scoreCalculationFactory = $scoreCalculationFactory;
        $this->clearDailyScoreFactory = $clearDailyScoreFactory;
        $this->indexerFactory = $indexerFactory;
        $this->configuration = $configuration;
    }

    public function execute()
    {
        if(!$this->configuration->isDailyCalculationEnabled()){
            return false;
        }
        $this->clearDailyScoreFactory->create()->clearDailyScoring();
        $this->scoreCalculationFactory->create()->recalculateScore();
        $this->indexerFactory->create()->invalidate();

        return true;
    }
}