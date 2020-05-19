<?php

namespace kfosoft\queue\components;

use kfosoft\queue\models\SchedulerQueueModel;
use kfosoft\queue\SchedulerQueueModelInterface;
use kfosoft\queue\ScheduledJobInterface;
use DateTime;
use DateTimeZone;
use Exception;
use Throwable;
use Yii;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;

class QueueScheduler
{
    public const COMPONENT_NAME = 'queue-scheduler';

    public $modelClassName = SchedulerQueueModel::class;

    public $queueComponentName = 'queue';

    public $queueChannel = 'default';

    public $db = 'db';

    public $daemonSleepTime = 60;

    /**
     * @return array|SchedulerQueueModelInterface[]
     * @throws Exception
     */
    public function getJobsForNow(): array
    {
        /** @var SchedulerQueueModelInterface $modelClass */
        $modelClass = $this->modelClassName;

        $time = (new DateTime('now', new DateTimeZone('UTC')))->getTimestamp();

        return $modelClass::getBeforeTime($this->queueChannel, $time);
    }

    /**
     * @param ScheduledJobInterface $job
     * @param int $runTime
     * @return bool
     * @throws Exception
     */
    public function enqueueAt(ScheduledJobInterface $job, int $runTime): bool
    {
        /** @var SchedulerQueueModelInterface $modelClass */
        $modelClass = $this->modelClassName;

        $jobClass = get_class($job);
        $jobParams = $job->getJobParams();
        $runTime = (new DateTime(sprintf('@%s', $runTime)))
            ->setTimezone(new DateTimeZone('UTC'))
            ->getTimestamp();

        if ($this->hasDelayed($job)) {
            Yii::debug(sprintf('Try to add existent job: Queue Channel: %s, Job Class: %s, Job Params: %s, Job Time: %s', $this->queueChannel, $jobClass, json_encode($jobParams), $runTime));
            return false;
        }

        /** @var SchedulerQueueModelInterface|ActiveRecord $model */
        $model = new $modelClass();
        $model->setQueueChannel($this->queueChannel);
        $model->setJobClass($jobClass);
        $model->setJobParams($jobParams);
        $model->setJobTime($runTime);

        $result = $model->save();

        Yii::debug(sprintf('Status of adding scheduled job: Queue Channel: %s, Job Class: %s, Job Params: %s, Job Time: %s, Saving result: %s', $this->queueChannel, $jobClass, json_encode($jobParams), $runTime, $result));

        return $result;
    }

    /**
     * @param ScheduledJobInterface $job
     * @return bool
     */
    public function hasDelayed(ScheduledJobInterface $job): bool
    {
        /** @var SchedulerQueueModelInterface $modelClass */
        $modelClass = $this->modelClassName;

        return (bool) $modelClass::getDelayed($this->queueChannel, get_class($job), $job->getJobParams());
    }

    /**
     * @param ScheduledJobInterface $job
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function removeDelayed(ScheduledJobInterface $job): void
    {
        /** @var SchedulerQueueModelInterface $modelClass */
        $modelClass = $this->modelClassName;

        $jobClass = get_class($job);
        $jobParams = $job->getJobParams();

        /** @var ActiveRecord $model */
        $model = $modelClass::getDelayed($this->queueChannel, $jobClass, $jobParams);

        if (null === $model) {
            Yii::debug(sprintf('Try to remove not existent job. Queue Channel: %s, Job Class: %s, Job Params: %s', $this->queueChannel, $jobClass, json_encode($jobParams)));
            return;
        }

        Yii::debug(sprintf('Status of removing scheduled job: Queue Channel: %s, Job Class: %s, Job Params: %s, Removing result %s', $this->queueChannel, $jobClass, json_encode($jobParams), $model->delete()));
    }
}
