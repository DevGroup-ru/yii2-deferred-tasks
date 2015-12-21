<?php

namespace DevGroup\DeferredTasks\structures;

use yii\base\Object;

class ReportingTaskResponse extends Object
{
    public $status = 0;
    public $error = false;
    public $errorMessage = '';
    public $lastFseekPosition = 0;
    public $newOutput = '';
    public $taskStatusCode = null;
}