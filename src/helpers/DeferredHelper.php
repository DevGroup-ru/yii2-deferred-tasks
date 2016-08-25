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
     *
     * @param integer $taskId
     *
     * @throws \Exception
     */
    public static function runImmediateTask($taskId)
    {
        $command = new ProcessBuilder();
        $command->setWorkingDirectory(Yii::getAlias('@app'));
        $command->setPrefix(self::getPhpBinary());
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            $command->add('yii');
        } else {
            $command->add('./yii');
        }
        $command->add('deferred/index')->add("$taskId");
        $process = $command->getProcess();
        $process->setWorkingDirectory(Yii::getAlias('@app'));
        $process->disableOutput();
        if (strncasecmp(PHP_OS, 'WIN', 3) !== 0) {
            $process->setCommandLine($process->getCommandLine() . ' &');
        }
        if (isset(Yii::$app->params['deferred.env'])) {
            $process->setEnv(Yii::$app->params['deferred.env']);
        }
        $process->mustRun();
    }

    /**
     * Helper method to find PHP binary path
     *
     * @return string
     */
    public static function getPhpBinary()
    {
        $binary = null;
        // HHVM
        if (defined('HHVM_VERSION') === true) {
            if (($binary = getenv('PHP_BINARY')) === false) {
                $binary = PHP_BINARY;
            }

            $binary = $binary . ' --php';
        }

        if ($binary === null) {
            $possibleBinaryLocations = [
                PHP_BINDIR . DIRECTORY_SEPARATOR . 'php',
                PHP_BINDIR . DIRECTORY_SEPARATOR . 'php-cli.exe',
                PHP_BINDIR . DIRECTORY_SEPARATOR . 'php.exe',
                getenv('PHP_BINARY'),
                getenv('PHP_BIN'),
                getenv('PHPBIN'),
            ];

            foreach ($possibleBinaryLocations as $bin) {
                if (is_readable($bin)) {
                    $binary = $bin;
                    break;
                }
            }
        }

        if ($binary === null) {
            $binary = 'php';
        }

        return $binary;
    }

}