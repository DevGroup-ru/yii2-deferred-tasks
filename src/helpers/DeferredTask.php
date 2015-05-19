<?php

namespace DevGroup\DeferredTasks\helpers;

use DevGroup\DeferredTasks\models\DeferredQueue;

class DeferredTask
{
    /**
     * @var DeferredQueue|null
     */
    protected $model = null;

    public function registerTask()
    {
        $this->model->planNextRun();
        return $this->model->save();
    }

    public function __construct($attributes=[])
    {
        $this->model = new DeferredQueue();
        $this->model->initiated_date = date("Y-m-d H:i:s");
        $this->model->setAttributes($attributes);
    }

    public function consoleRoute($route, $arguments=[])
    {
        $this->model->console_route = $route;
        $this->setArguments($arguments);
        return $this;
    }

    public function cliCommand($command, $arguments=[])
    {
        $this->model->cli_command = $command;
        $this->setArguments($arguments);
        return $this;
    }

    private function setArguments($arguments=[])
    {
        if (is_array($arguments) === true) {
            $this->model->command_arguments = implode("\n", $arguments);
        } else {
            $this->model->command_arguments = (string) $arguments;
        }
        return $this;
    }
}