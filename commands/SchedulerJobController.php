<?php

namespace kfosoft\queue\commands;

use kfosoft\queue\SchedulerQueueModelInterface;
use kfosoft\queue\components\QueueScheduler;
use kfosoft\daemon\Daemon;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\queue\Queue;

class SchedulerJobController extends Daemon
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
    public function doJob(array $job): bool
    {
        Yii::debug(sprintf('Triggered scheduler queue worker with channel %s', $this->queueScheduler));

        $jobsForNow = $this->queueScheduler->getJobsForNow();
        Yii::debug(sprintf('Count of scheduled jobs for now %s', count($jobsForNow)));

        /** @var SchedulerQueueModelInterface|ActiveRecord $jobConfig */
        foreach ($jobsForNow as $jobConfig) {
            $jobClass = $jobConfig->getJobClass();
            $this->queueManager->push(new $jobClass($jobConfig->getJobParams()));

            Yii::debug(sprintf('Send job %s with params %s to queue. Time for run %s', $jobClass, json_encode($jobConfig->getJobParams()), $jobConfig->getJobTime()));

            $jobConfig->delete();

            Yii::debug(sprintf('The job %s with params %s was deleted. Time for run %s', $jobClass, json_encode($jobConfig->getJobParams()), $jobConfig->getJobTime()));
        }

        sleep($this->queueScheduler->daemonSleepTime);

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function defineJobs()
    {
        return [];
    }
}
