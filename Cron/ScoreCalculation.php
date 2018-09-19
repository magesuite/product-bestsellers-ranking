<?php
namespace MageSuite\ProductBestsellersRanking\Cron;

class ScoreCalculation
{
    /**
     * @var \MageSuite\ProductBestsellersRanking\Model\ScoreCalculation
     */
    protected $scoreCalculation;

    /**
     * @var \MageSuite\ProductBestsellersRanking\Model\ClearDailyScore
     */
    private $clearDailyScore;
    /**
     * @var \MageSuite\ProductBestsellersRanking\Model\Indexer
     */
    private $indexer;

    /**
     * ScoreCalculation constructor.
     * @param \MageSuite\ProductBestsellersRanking\Model\ScoreCalculation $scoreCalculation
     * @param \MageSuite\ProductBestsellersRanking\Model\ClearDailyScore $clearDailyScore
     * @param \MageSuite\ProductBestsellersRanking\Model\Indexer $indexer
     */
    public function __construct(
        \MageSuite\ProductBestsellersRanking\Model\ScoreCalculation $scoreCalculation,
        \MageSuite\ProductBestsellersRanking\Model\ClearDailyScore $clearDailyScore,
        \MageSuite\ProductBestsellersRanking\Model\Indexer $indexer
    )
    {
        $this->scoreCalculation = $scoreCalculation;
        $this->clearDailyScore = $clearDailyScore;
        $this->indexer = $indexer;
    }

    public function execute()
    {
        $this->clearDailyScore->clearDailyScoring();
        $this->scoreCalculation->recalculateScore();
        $this->indexer->reindex();
    }
}