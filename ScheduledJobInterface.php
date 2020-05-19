<?php

namespace kfosoft\queue;

use yii\queue\JobInterface;

interface ScheduledJobInterface extends JobInterface
{
    /**
     * @return array
     */
    public function getJobParams(): array;
}