<?php

namespace DevGroup\DeferredTasks\helpers;

use Yii;

class ReportingTask extends OnetimeTask
{
    public function __construct($attributes=[])
    {
        parent::__construct($attributes);
        $this->model->delete_after_run = false;
        $this->model->output_file = Yii::getAlias('@runtime') . '/deferred-task-'.uniqid();
    }
}