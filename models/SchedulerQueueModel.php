<?php

namespace kfosoft\queue\models;

use kfosoft\queue\ScheduledJobInterface;
use kfosoft\queue\SchedulerQueueModelInterface;
use yii\base\Exception;
use yii\db\ActiveRecord;

/**
 * @property int          $id           primary key
 * @property string       $queueChannel queue channel
 * @property string       $jobClass     this class should to implement SchedulerJobInterface
 * @property string|array $jobParams    job params (json)
 * @property int          $jobTime      time for send to queue
 */
class SchedulerQueueModel extends ActiveRecord implements SchedulerQueueModelInterface
{
    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public static function getDelayed(string $queueChannel, string $jobClass, array $jobParams): ?ScheduledJobInterface
    {
        return static::find()
            ->where(['queueChannel' => ':qc', 'jobClass' => ':jc', 'jobParams' => ':jp'])
            ->params([':qc' => $queueChannel, ':jc' => $jobClass, ':jp' => static::serializeToJson($jobParams)])
            ->one();
    }

    /**
     * {@inheritdoc}
     */
    public static function getBeforeTime(string $queueChannel, int $time): array
    {
        return static::find()
            ->where(['queueChannel' => ':qc', 'jobTime' => ':jt'])
            ->params([':qc' => $queueChannel, ':jt' => $time])
            ->all();
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function afterFind(): void
    {
        $this->setAttribute('jobParams', self::deserializeFromJson($this->getAttribute('jobParams')));
        parent::afterFind();
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function beforeSave($insert): bool
    {
        $this->setAttribute('jobParams', self::serializeToJson($this->getAttribute('jobParams')));
        return parent::beforeSave($insert);
    }

    /**
     * {@inheritdoc}
     */
    public function getJobClass(): string
    {
        return $this->jobClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getJobParams(): array
    {
        return $this->jobParams;
    }

    /**
     * {@inheritdoc}
     */
    public function getJobTime(): int
    {
        return $this->jobTime;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueChannel(): string
    {
        return $this->queueChannel;
    }

    /**
     * {@inheritdoc}
     */
    public function setJobClass(string $class): void
    {
        $this->setAttribute('jobClass', $class);
    }

    /**
     * {@inheritdoc}
     */
    public function setJobParams(array $params): void
    {
        $this->setAttribute('jobParams', $params);
    }

    /**
     * {@inheritdoc}
     */
    public function setJobTime(int $time): void
    {
        $this->setAttribute('jobTime', $time);
    }

    /**
     * {@inheritdoc}
     */
    public function setQueueChannel(string $channelName): void
    {
        $this->setAttribute('queueChannel', $channelName);
    }

    /**
     * @param array $data
     * @return string
     * @throws Exception
     */
    private static function serializeToJson(array $data): string
    {
        $result = json_encode($data);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new Exception(sprintf('Scheduler job serialization error: %s', json_last_error_msg()));
        }

        return $result;
    }

    /**
     * @param string $data
     * @return array
     * @throws Exception
     */
    private static function deserializeFromJson(string $data): array
    {
        $result = json_decode($data, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new Exception(sprintf('Scheduler job deserialization error: %s', json_last_error_msg()));
        }

        return $result;
    }
}
