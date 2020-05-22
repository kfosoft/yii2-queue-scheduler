<?php

namespace kfosoft\queue\components;

use kfosoft\queue\commands\SchedulerDaemonController;
use kfosoft\queue\models\SchedulerQueueModel;
use kfosoft\queue\SchedulerQueueModelInterface;
use kfosoft\queue\ScheduledJobInterface;
use DateTime;
use DateTimeZone;
use Exception;
use Throwable;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\console\Application as ConsoleApplication;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;
use yii\helpers\ArrayHelper;
use yii\log\Logger;

/**
 * @package kfosoft\queue\components
 * @version 20.05
 * @author (c) KFOSOFT <kfosoftware@gmail.com>
 */
class QueueScheduler extends Component implements BootstrapInterface
{
    /**
     * Component name
     */
    public const COMPONENT_NAME = 'queue-scheduler';

    /**
     * @var string you can change model class name for don't use SchedulerQueueModel. But this class has to be implement the SchedulerQueueModelInterface.
     */
    public $modelClassName = SchedulerQueueModel::class;

    /**
     * @var string you can change name of database table without change SchedulerQueueModel
     */
    public $tableName = 'SchedulerQueue';

    /**
     * @var string name of queue component
     * @see yiisoft/yii2-queue
     */
    public $queueComponentName = 'queue';

    /**
     * @var string name of database component
     */
    public $db = 'db';

    /**
     * @var int this timeout defines time between reading of SchedulerQueue database
     */
    public $daemonSleepTime = 10;

    /**
     * {@inheritdoc}
     * @throws Throwable
     */
    public function bootstrap($app): void
    {
        if ($app instanceof ConsoleApplication) {
            $request = $app->request->resolve();
            if (preg_match('/migrate\//', $request[0])) {
                return;
            } elseif (preg_match('/migrate/', $request[0])) {
                return;
            }

            $app->controllerMap = ArrayHelper::merge($app->controllerMap, ['queue-scheduler' => SchedulerDaemonController::class]);
        }
    }

    /**
     * @return array|SchedulerQueueModelInterface[]
     * @throws Exception
     */
    public function getJobsForNow(): array
    {
        /** @var SchedulerQueueModelInterface $modelClass */
        $modelClass = $this->modelClassName;

        $time = (new DateTime('now', new DateTimeZone('UTC')))->getTimestamp();

        return $modelClass::getBeforeTime($time);
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
            $this->log(sprintf('Try to add existent job: Job Class: %s, Job Params: %s, Job Time: %s', $jobClass, json_encode($jobParams), $runTime));
            return false;
        }

        /** @var SchedulerQueueModelInterface|ActiveRecord $model */
        $model = new $modelClass();
        $model->setJobClass($jobClass);
        $model->setJobParams($jobParams);
        $model->setJobTime($runTime);

        $result = $model->save();

        $this->log(sprintf('Status of adding scheduled job: Job Class: %s, Job Params: %s, Job Time: %s, Saving result: %s', $jobClass, json_encode($jobParams), $runTime, $result));

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

        return (bool) $modelClass::getDelayed(get_class($job), $job->getJobParams());
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
        $model = $modelClass::getDelayed($jobClass, $jobParams);

        if (null === $model) {
            $this->log(sprintf('Try to remove not existent job. Job Class: %s, Job Params: %s', $jobClass, json_encode($jobParams)));
            return;
        }

        $this->log(sprintf('Status of removing scheduled job: Job Class: %s, Job Params: %s, Removing result %s', $jobClass, json_encode($jobParams), $model->delete()));
    }

    /**
     * @param string $message
     * @param int $level
     */
    public function log(string $message, $level = Logger::LEVEL_TRACE): void
    {
        Yii::$app->log->logger->log($message, $level, 'queue');
        Yii::$app->log->logger->flush(true);
    }
}
