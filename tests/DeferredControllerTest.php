<?php

namespace DevGroup\DeferredTasks\Tests;

use DevGroup\DeferredTasks\commands\DeferredController;
use DevGroup\DeferredTasks\models\DeferredQueue;
use Yii;
use yii\db\Connection;
use yii\helpers\ArrayHelper;


/**
 * MysqlTaggableBehaviorTest
 */
class DeferredControllerTest extends \PHPUnit_Extensions_Database_TestCase
{
    /** @var DeferredController */
    private $_controller;

    /**
     * @inheritdoc
     */
    public function getConnection()
    {
        $this->_controller = Yii::createObject([
            'class' => DeferredController::className(),
        ],[null,null]);

        $this->mockApplication();
        Yii::$app->db->open();
        return $this->createDefaultDBConnection(\Yii::$app->db->pdo);
    }

    /**
     * @inheritdoc
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(__DIR__ . '/data/test.xml');
    }


    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication($config = [], $appClass = '\yii\console\Application')
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => '../../vendor',
            'components' => [
                'mutex' => [
                    'class' => 'yii\mutex\MysqlMutex',
                ],
                'db' => [
                    'class' => Connection::className(),
                    'dsn' => 'mysql:host=localhost;dbname=yii2_deferred_tasks',
                    'username' => 'root',
                    'password' => '',
                ],
            ],
        ], $config));
    }

    public function testGetNextTasks()
    {
        $this->getConnection()->createDataSet(['deferred_group', 'deferred_queue']);
        $time = mktime(19,40,0,5,19,2015);

        $tasks = DeferredQueue::getNextTasks($time);

        $disabledTaskExists = false;
        $futureTaskExists = false;
        foreach ($tasks as $task) {
            if ($task->id === 1) {
                $disabledTaskExists = true;
            }
            if ($task->id === 2) {
                $futureTaskExists = true;
            }
        }
        $this->assertTrue($disabledTaskExists === false);
        $this->assertTrue($futureTaskExists === false);

    }
}
