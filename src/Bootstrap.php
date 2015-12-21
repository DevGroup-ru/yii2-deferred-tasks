<?php

namespace DevGroup\DeferredTasks;

use DevGroup\DeferredTasks\commands\DeferredController;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{
    /**
     * Bootstrap method to be called during application bootstrap stage.
     *
     * @param Application $app the application currently running
     */
    public function bootstrap($app)
    {
        if ($app instanceof \yii\console\Application) {

            // add deferred module
            $app->setModule('deferred', new DeferredTasksModule('deferred', $app));

            // this will automatically add deferred controller to console app
            $app->controllerMap['deferred'] = [
                'class' => DeferredController::className(),
            ];
        }
    }
}