<?php

namespace MageSuite\ProductBestsellersRanking\Service;

class CronJobCrashDetector
{
    public const LOCK_NAME = 'bestseller_score_calculation';

    protected \Magento\Framework\Lock\LockManagerInterface $lockManager;
    protected ?\Magento\Framework\DB\Adapter\AdapterInterface $connection;
    protected \Psr\Log\LoggerInterface $logger;

    public function __construct(
        \Magento\Framework\Lock\LockManagerInterface $lockManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->lockManager = $lockManager;
        $this->connection = $resourceConnection->getConnection();
        $this->logger = $logger;
    }

    public function execute()
    {
        $cronScheduleTableName = $this->connection->getTableName('cron_schedule');

        $select = $this->connection->select();
        $select->from($cronScheduleTableName);
        $select->where('job_code = ?', 'bestseller_score_calculation');
        $select->where('status = ?', 'running');

        $runningJob = $this->connection->fetchRow($select);

        if (!$runningJob) {
            return;
        }

        $isLocked = $this->lockManager->isLocked(self::LOCK_NAME);

        if ($isLocked) {
            return;
        }

        $fiveMinutesFromNow = (new \DateTime('now'))->add(new \DateInterval('PT5M'))->format('Y-m-d H:i:00');

        $this->connection->update(
            $cronScheduleTableName,
            [
                'status' => 'pending',
                'created_at' => $fiveMinutesFromNow,
                'scheduled_at' => $fiveMinutesFromNow,
                'executed_at' => null
            ],
            ['schedule_id = ?' => $runningJob['schedule_id']]
        );

        $this->logger->error(sprintf(
            'Bestseller calculation cron job crashed, trying to rerun it at %s.',
            $fiveMinutesFromNow
        ));
    }
}
