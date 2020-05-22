<?php

namespace kfosoft\queue;

use yii\queue\JobInterface;

/**
 * @package kfosoft\queue
 * @version 20.05
 * @author (c) KFOSOFT <kfosoftware@gmail.com>
 */
interface ScheduledJobInterface extends JobInterface
{
    /**
     * @return array
     */
    public function getJobParams(): array;
}