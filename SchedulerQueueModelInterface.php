<?php

namespace kfosoft\queue;

/**
 * @package kfosoft\queue
 * @version 20.05
 * @author (c) KFOSOFT <kfosoftware@gmail.com>
 */
interface SchedulerQueueModelInterface
{
    /**
     * @param string $jobClass
     * @param array  $jobParams
     * @return self|null
     */
    public static function getDelayed(string $jobClass, array $jobParams): ?self;

    /**
     * @param int $time
     * @return array|SchedulerQueueModelInterface[]
     */
    public static function getBeforeTime(int $time): array;

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
}