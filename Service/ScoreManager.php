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
     * @var \MageSuite\ProductBestsellersRanking\Model\IndexerFactory
     */
    protected $indexerFactory;

    /**
     * @var \MageSuite\ProductBestsellersRanking\Helper\Configuration
     */
    protected $configuration;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;


    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \MageSuite\ProductBestsellersRanking\Model\ClearDailyScoreFactory $clearDailyScoreFactory,
        \MageSuite\ProductBestsellersRanking\Model\ScoreCalculationFactory $scoreCalculationFactory,
        \MageSuite\ProductBestsellersRanking\Model\IndexerFactory $indexerFactory,
        \MageSuite\ProductBestsellersRanking\Helper\Configuration $configuration,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->clearDailyScoreFactory = $clearDailyScoreFactory;
        $this->scoreCalculationFactory = $scoreCalculationFactory;
        $this->indexerFactory = $indexerFactory;
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    public function recalculateScores()
    {
        if ($this->configuration->isUseTransactionsEnabled()) {
            $connection = $this->resourceConnection->getConnection();
            $connection->beginTransaction();
        }

        try {
            $this->clearDailyScoreFactory->create()->clearDailyScoring();
            $this->scoreCalculationFactory->create()->recalculateScore();
            $this->indexerFactory->create()->invalidate();
            if ($this->configuration->isUseTransactionsEnabled()) {
                $connection->commit();
            }
        } catch (\Exception | \Error $exception) {
            $this->logger->critical(sprintf(
                'Error during Bestseller score recalculation: %s',
                $exception->getMessage()
            ));
            if ($this->configuration->isUseTransactionsEnabled()) {
                $connection->rollBack();
            }
            throw $exception;
        }
    }
}
