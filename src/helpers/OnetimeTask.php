<?php

namespace DevGroup\DeferredTasks\helpers;

use DevGroup\DeferredTasks\helpers\DeferredTask;

class OnetimeTask extends DeferredTask
{
    public function __construct($attributes=[])
    {
        parent::__construct($attributes);
        $this->model->is_repeating_task = false;
        $this->model->cron_expression = '* * * * *';
    }
}