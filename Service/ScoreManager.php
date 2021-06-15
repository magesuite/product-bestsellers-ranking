<?php

namespace MageSuite\ProductBestsellersRanking\Service;

class ScoreManager
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var \MageSuite\ProductBestsellersRanking\Model\ClearDailyScoreFactory
     */
    protected $clearDailyScoreFactory;

    /**
     * @var \MageSuite\ProductBestsellersRanking\Model\ScoreCalculationFactory
     */
    protected $scoreCalculationFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \MageSuite\ProductBestsellersRanking\Model\ClearDailyScoreFactory $clearDailyScoreFactory,
        \MageSuite\ProductBestsellersRanking\Model\ScoreCalculationFactory $scoreCalculationFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->clearDailyScoreFactory = $clearDailyScoreFactory;
        $this->scoreCalculationFactory = $scoreCalculationFactory;
        $this->logger = $logger;
    }

    public function recalculateScores()
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        try {
            $this->clearDailyScoreFactory->create()->clearDailyScoring();
            $this->scoreCalculationFactory->create()->recalculateScore();
            $connection->commit();
        } catch (\Exception | \Error $exception) {
            $this->logger->critical(sprintf(
                'Error during Bestseller score recalculation: %s',
                $exception->getMessage()
            ));

            $connection->rollBack();
            throw $exception;
        }
    }
}
