<?php

namespace DevGroup\DeferredTasks;

use DevGroup\DeferredTasks\commands\DeferredController;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Module;

class DeferredTasksModule extends Module implements BootstrapInterface
{

    /**
     * Bootstrap method to be called during application bootstrap stage.
     *
     * @param Application $app the application currently running
     */
    public function bootstrap($app)
    {
        if (Yii::$app instanceof \yii\console\Application) {
            // this will automatically add deferred controller to console app
            Yii::$app->controllerMap['deferred'] = [
                'class' => DeferredController::className(),
            ];
        }
    }
}