<?php

namespace kfosoft\queue\commands;

use kfosoft\daemon\SingleJobInterface;
use kfosoft\queue\SchedulerQueueModelInterface;
use kfosoft\queue\components\QueueScheduler;
use kfosoft\daemon\Daemon;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\queue\Queue;

/**
 * Runs queue scheduler daemon.
 * @package kfosoft\queue\commands
 * @version 20.06
 * @author (c) KFOSOFT <kfosoftware@gmail.com>
 */
class SchedulerDaemonController extends Daemon implements SingleJobInterface
{
    /**
     * @var Queue
     */
    private $queueManager;

    /**
     * @var QueueScheduler
     */
    protected $queueScheduler;

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        $this->queueScheduler = Yii::$app->get(QueueScheduler::COMPONENT_NAME);
        $this->queueManager = Yii::$app->get($this->queueScheduler->queueComponentName);
        parent::init();
    }

    /**
     * {@inheritdoc}
     * @throws Throwable
     */
    public function __invoke($job): bool
    {
        $this->queueScheduler->log(sprintf('Triggered scheduler queue worker for queue component "%s"', $this->queueScheduler->queueComponentName));

        $jobsForNow = $this->queueScheduler->getJobsForNow();
        $this->queueScheduler->log(sprintf('Count of scheduled jobs for now %s', count($jobsForNow)));

        /** @var SchedulerQueueModelInterface|ActiveRecord $jobConfig */
        foreach ($jobsForNow as $jobConfig) {
            $jobClass = $jobConfig->getJobClass();
            $this->queueManager->push(new $jobClass($jobConfig->getJobParams()));

            $this->queueScheduler->log(sprintf('Send job %s with params %s to queue. Time for run %s', $jobClass, json_encode($jobConfig->getJobParams()), $jobConfig->getJobTime()));

            $jobConfig->delete();

            $this->queueScheduler->log(sprintf('The job %s with params %s was deleted. Time for run %s', $jobClass, json_encode($jobConfig->getJobParams()), $jobConfig->getJobTime()));

        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function sleepTime(): int
    {
        return $this->queueScheduler->daemonSleepTime;
    }
}
