<?php

namespace DevGroup\DeferredTasks\helpers;

use Yii;

class ReportingTask extends OnetimeTask
{
    public function __construct($attributes=[])
    {
        parent::__construct($attributes);
        $this->model->delete_after_run = false;
        $this->model->output_file = Yii::getAlias('@runtime') . DIRECTORY_SEPARATOR . 'deferred-task-' . uniqid();
        touch($this->model->output_file);
        chmod($this->model->output_file, 0777);
    }
}