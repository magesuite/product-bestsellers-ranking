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
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private \Psr\Log\LoggerInterface $logger;

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
        \MageSuite\ProductBestsellersRanking\Helper\Configuration $configuration,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->scoreCalculationFactory = $scoreCalculationFactory;
        $this->clearDailyScoreFactory = $clearDailyScoreFactory;
        $this->indexerFactory = $indexerFactory;
        $this->configuration = $configuration;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    public function execute()
    {
        if (!$this->configuration->isDailyCalculationEnabled()){
            return false;
        }

        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();
        try {
            $this->clearDailyScoreFactory->create()->clearDailyScoring($connection);
            $this->scoreCalculationFactory->create()->recalculateScore($connection);
            $connection->commit();
        } catch (\Exception $exception) {
            $this->logger->critical($exception);
            $connection->rollBack();
            throw $exception;
        }

        $this->indexerFactory->create()->invalidate();
        return true;
    }
}
