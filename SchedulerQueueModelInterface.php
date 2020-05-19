<?php

namespace kfosoft\queue;

interface SchedulerQueueModelInterface
{
    /**
     * @param string $queueChannel
     * @param string $jobClass
     * @param array  $jobParams
     * @return ScheduledJobInterface|null
     */
    public static function getDelayed(string $queueChannel, string $jobClass, array $jobParams): ?ScheduledJobInterface;

    /**
     * @param string $queueChannel
     * @param int $time
     * @return array|ScheduledJobInterface[]
     */
    public static function getBeforeTime(string $queueChannel, int $time): array;

    /**
     * @return string queue name
     */
    public function getQueueChannel(): string;

    /**
     * @return string job class
     */
    public function getJobClass(): string;

    /**
     * @return array job params
     */
    public function getJobParams(): array;

    /**
     * @return int time to send job to queue
     */
    public function getJobTime(): int;

    /**
     * @param string $class this class should to implement ScheduledJobInterface
     */
    public function setJobClass(string $class): void;

    /**
     * @param array $params must contain only scalar values(not objects or resources)
     */
    public function setJobParams(array $params): void;

    /**
     * @param int $time time to send job to queue
     */
    public function setJobTime(int $time): void;

    /**
     * @param string $channelName name of queue channel
     */
    public function setQueueChannel(string $channelName): void;
}