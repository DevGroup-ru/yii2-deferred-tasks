<?php
return [
    'id' => 'testapp',
    'basePath' => dirname(__DIR__),
    'vendorPath' => '../../vendor',
    'controllerMap' => [
        'deferred' => [
            'class' => DevGroup\DeferredTasks\commands\DeferredController::className(),
        ],
    ],
    'components' => [
        'mutex' => [
            'class' => 'yii\mutex\MysqlMutex',
            'autoRelease' => false,
        ],
        'db' => [
            'class' => yii\db\Connection::className(),
            'dsn' => 'mysql:host=localhost;dbname=yii2_deferred_tasks',
            'username' => 'root',
            'password' => '',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
    ],
    'params'=> [
            'deferred.env' => [
                'COMPOSER_HOME' => dirname(__DIR__),
            ],
    ]
];