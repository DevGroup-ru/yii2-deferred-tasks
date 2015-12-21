<?php

namespace DevGroup\DeferredTasks\assets;

use yii\web\AssetBundle;

class AdminBundle extends AssetBundle
{
    public $js = [
        'main.js',
    ];

    public $css = [
        'main.css',
    ];

    public function init()
    {
        parent::init();
        $this->sourcePath = __DIR__ . DIRECTORY_SEPARATOR . 'dist/';
    }

    public $depends = [
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapPluginAsset',
    ];
}
