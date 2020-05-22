### Yii2 Queue Scheduler
Yii2 extension for `yiisoft/yii2-queue` library

### Install
`composer require kfosoft/yii2-queue-scheduler`

### Configure
For both(web, console) configs
```
    'components' => [
        kfosoft\queue\components\QueueScheduler::COMPONENT_NAME => [
            'class'              => kfosoft\queue\components\QueueScheduler::class,
            'modelClassName'     => 'default', // you can change model class name for don't use SchedulerQueueModel. But this class has to be implement the SchedulerQueueModelInterface
            'tableName'          => kfosoft\queue\models\SchedulerQueueModel::tableName(), // you can change name of database table without change SchedulerQueueModel
            'queueComponentName' => 'queue', // name of queue component yiisoft/yii2-queue
            'db'                 => 'db', // name of database component
            'daemonSleepTime'    => 10, // this timeout defines time between reading of SchedulerQueue database
        ],
        ...
    ],
    ...
```

For console config
```
    'bootstrap' => ['queue', kfosoft\queue\components\QueueScheduler::COMPONENT_NAME, ...],
    ...
```

And migrate the migration from repository

### To run daemon
```
bin/yii queue-scheduler
```
NOTE: To make it work properly you have to choose one of the options: 
* `supervisor` look at the supervisor config in repository
* `cron` with WatcherDaemon(NOT TESTED).
```
namespace console\controllers;

use kfosoft\daemon\WatcherDaemon;

class WatcherDaemonController extends WatcherDaemon
{
    /**
     * @return array
     */
    protected function defineJobs()
    {
        sleep($this->sleep);

        //TODO: modify list, or get it from config, it does not matter
        $daemons = [
            ['className' => \kfosoft\queue\commands\SchedulerDaemonController::class, 'enabled' => true],
        ];

        return $daemons;
    }
}
```
Then add this line to crontab 
```
* * * * * /path/to/yii/project/yii watcher-daemon --demonize=1
```
### Using
* In order to use scheduler you have to create job and implement `ScheduledJobInterface`. Also you have to implement `getJobParams` method in job class. It has to return the array fields values for creating a job. For example:
```
<?php

namespace app\jobs;

use kfosoft\queue\ScheduledJobInterface;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;

class ImpotantJob extends BaseObject implements ScheduledJobInterface
{
    /**
     * @var int
     */
    public $param1;

    /**
     * @var string
     */
    public $param2;

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public function execute($queue): void
    {
        // Your very important logic
    }

    /**
     * {@inheritdoc}
     */
    public function getJobParams(): array
    {
        return [
            'param1' => $this->param1,
            'param2' => $this->param2
        ];
    }
}

```

* You can schedule a job for example in 5 minutes or at 7AM next day.
```
// In 5 min
$dateTime = (new \DateTime())->modify('+5 minutes');
$job = new \app\jobs\ImpotantJob(['param1' => 1, 'param2' => 'Very important text']);

Yii::$app->get(\kfosoft\queue\components\QueueScheduler::COMPONENT_NAME)
    ->enqueueAt($job, $dateTime->getTimestamp());
```
```
// Next day at 7AM
$dateTime = (new \DateTime())->modify('+1 day')->setTime(7,0,0);
$job = new \app\jobs\ImpotantJob(['param1' => 1, 'param2' => 'Very important text']);

Yii::$app->get(\kfosoft\queue\components\QueueScheduler::COMPONENT_NAME)
    ->enqueueAt($job, $dateTime->getTimestamp());
```
NOTE: All jobs run in UTC timezone. Please set timestamp for `enqueueAt` method(The second param) in server's timezone because the component converts the timezone to UTC automatically.

* You can check scheduled jobs if needed.
```
$job = new \app\jobs\ImpotantJob(['param1' => 1, 'param2' => 'Very important text']);
$hasJob = Yii::$app->get(\kfosoft\queue\components\QueueScheduler::COMPONENT_NAME)
    ->hasDelayed($job); // bool returns
```

* Also you can remove the scheduled jobs if needed.
```
$job = new \app\jobs\ImpotantJob(['param1' => 1, 'param2' => 'Very important text']);
Yii::$app->get(\kfosoft\queue\components\QueueScheduler::COMPONENT_NAME)
    ->removeDelayed($job);
```

* Also you can get the scheduled jobs for current moment.
```
Yii::$app->get(\kfosoft\queue\components\QueueScheduler::COMPONENT_NAME)
    ->getJobsForNow(); // returns array jobs
```
