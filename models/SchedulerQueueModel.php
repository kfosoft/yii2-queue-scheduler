<?php

namespace kfosoft\queue\models;

use kfosoft\queue\components\QueueScheduler;
use kfosoft\queue\SchedulerQueueModelInterface;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use Ramsey\Uuid\Uuid;

/**
 * @property string       $uuid         primary key
 * @property string       $jobClass     this class should to implement SchedulerJobInterface
 * @property string|array $jobParams    job params (json)
 * @property int          $jobTime      time for send to queue
 *
 * @package kfosoft\queue\models
 * @version 20.05
 * @author (c) KFOSOFT <kfosoftware@gmail.com>
 */
class SchedulerQueueModel extends ActiveRecord implements SchedulerQueueModelInterface
{
    /**
     * {@inheritdoc}
     */
    public function beforeValidate(): bool
    {
        if (null === $this->uuid) {
            $this->uuid = Uuid::uuid4()->toString();
        }

        return parent::beforeValidate();
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return ArrayHelper::merge([
            ['uuid', 'match', 'pattern'=>'/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i'],
            [['uuid', 'jobClass', 'jobParams', 'jobTime'], 'required'],
            ['jobTime', 'integer', 'integerOnly' => true,],
        ], parent::rules());
    }

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    public static function tableName(): string
    {
        return Yii::$app->get(QueueScheduler::COMPONENT_NAME)->tableName;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public static function getDelayed(string $jobClass, array $jobParams): ?SchedulerQueueModelInterface
    {
        return static::find()
            ->where(['jobClass' => $jobClass, 'jobParams' => static::serializeToJson($jobParams)])
            ->one();
    }

    /**
     * {@inheritdoc}
     */
    public static function getBeforeTime(int $time): array
    {
        return static::find()
            ->where('jobTime <= :jt', [':jt' => $time])
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
