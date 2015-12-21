<?php

namespace DevGroup\DeferredTasks\helpers;

use Symfony\Component\Process\ProcessBuilder;
use Yii;

/**
 * Class DeferredHelper is the main helper class for deferred tasks module.
 *
 * @package DevGroup\DeferredTasks\helpers
 */
class DeferredHelper
{
    /**
     * Performs immediate task start - fire and forget.
     * Used in web requests to start task in background.
     * @param integer $taskId
     */
    public static function runImmediateTask($taskId)
    {
        $command = new ProcessBuilder();
        $command->setWorkingDirectory(Yii::getAlias('@app'));
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            $command
                ->setPrefix('yii.bat');
        } else {
            $command
                ->setPrefix('./yii');
        }
        $command
            ->add('deferred/index')
            ->add("$taskId");
        $process = $command->getProcess();
        $process->setWorkingDirectory(Yii::getAlias('@app'));
        $process->setCommandLine($process->getCommandLine() . ' > /dev/null 2>&1 &');
        if (isset(Yii::$app->params['deferred.env'])) {
            $process->setEnv(Yii::$app->params['deferred.env']);
        }
        $process->run();
    }

}