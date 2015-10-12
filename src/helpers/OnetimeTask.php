<?php

namespace DevGroup\DeferredTasks\helpers;

use Cron\CronExpression;
use DevGroup\DeferredTasks\models\DeferredQueue;

class OnetimeTask extends DeferredTask
{
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
        $this->model->is_repeating_task = false;
        $this->model->cron_expression = '* * * * *';
        $this->model->status = DeferredQueue::STATUS_SCHEDULED;
        $cron = CronExpression::factory($this->model->cron_expression);
        $this->model->next_start = date('Y-m-d H:i:s', $cron->getNextRunDate()->getTimestamp());
    }
}
