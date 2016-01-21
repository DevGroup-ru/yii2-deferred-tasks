<?php

namespace DevGroup\DeferredTasks\Tests;

use DevGroup\DeferredTasks\commands\DeferredController;
use DevGroup\DeferredTasks\events\DeferredQueueCompleteEvent;
use DevGroup\DeferredTasks\handlers\QueueCompleteEventHandler;
use DevGroup\DeferredTasks\helpers\DeferredHelper;
use DevGroup\DeferredTasks\helpers\OnetimeTask;
use DevGroup\DeferredTasks\helpers\ReportingChain;
use DevGroup\DeferredTasks\helpers\ReportingTask;
use DevGroup\DeferredTasks\models\DeferredGroup;
use DevGroup\DeferredTasks\models\DeferredQueue;
use DevGroup\ExtensionsManager\ExtensionsManager;
use Symfony\Component\Process\Process;
use Yii;
use yii\db\Connection;
use yii\helpers\ArrayHelper;

/**
 * DeferredControllerTest
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
        return $this->createDefaultDBConnection(\Yii::$app->getDb()->pdo);
    }

    /**
     * @inheritdoc
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(__DIR__ . '/data/test.xml');
    }
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->mockApplication();
        $this->_controller = Yii::createObject([
            'class' => DeferredController::className(),
        ], [null, null]);
        parent::setUp();
    }

    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication($config = [], $appClass = '\yii\console\Application')
    {
        $appConfig = require('config/testapp.php');
        new $appClass(ArrayHelper::merge($appConfig, $config));
        Yii::$app->cache->flush();
        Yii::$app->getDb()->open();
        Yii::$app->runAction('migrate/down', [99999, 'interactive' => 0, 'migrationPath' => __DIR__ . '/../src/migrations/']);
        Yii::$app->runAction('migrate/up', ['interactive' => 0, 'migrationPath' => __DIR__ . '/../src/migrations/']);
    }

    /**
     * Clean up after test.
     * By default the application created with [[mockApplication]] will be destroyed.
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->destroyApplication();
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        if (\Yii::$app && \Yii::$app->has('session', true)) {
            \Yii::$app->session->close();
        }
        \Yii::$app = null;
    }

    public function testGetNextTasks()
    {

        $time = mktime(19, 40, 0, 5, 19, 2015);

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

    public function testRunProcesses()
    {
        $files = [
            'task3',
            'task4',
            'task5',
            'task6',
            'task7',
            'task8',
            'task9',
            'task10',
            'task11',
        ];
        foreach ($files as $f) {
            if (file_exists("/tmp/$f")) {
                unlink("/tmp/$f");
            }
        }
        $time = mktime(19, 40, 0, 5, 19, 2015);
        $this->_controller->actionIndex('0', $time, 1);
        $this->assertTrue(file_exists('/tmp/task3'));
        $this->assertTrue(file_exists('/tmp/task4'));
        $this->assertTrue(file_exists('/tmp/task5')===false);
        $this->assertTrue(file_exists('/tmp/task6')===false);
        $this->assertTrue(file_exists('/tmp/task7'));
        $this->assertTrue(file_exists('/tmp/task8'));
        $this->assertTrue(file_exists('/tmp/task9'));
        $this->assertTrue(file_exists('/tmp/task10'));
        $this->assertTrue(file_exists('/tmp/task11'));
    }

    public function testRegister()
    {
        $files = [
            'task91',
        ];
        foreach ($files as $f) {
            if (file_exists("/tmp/$f")) {
                unlink("/tmp/$f");
            }
        }
        $task = new OnetimeTask();
        $task->cliCommand('touch', ['/tmp/task91']);

        $this->assertTrue($task->registerTask());
        $time = time()+120;
        echo "Running $time = " . date("Y-m-d H:i:s", $time) . "\n";

        $this->assertInstanceOf(DeferredQueue::className(), $task->model());

        Yii::$app->runAction('deferred/index', [0, $time, 1]);

        echo "Checking\n";
        $this->assertTrue(file_exists('/tmp/task91'));
    }

    public function testReportingChain()
    {
        $files = [
            'task201',
            'task202',
        ];
        $testChain = new ReportingChain();
        $this->assertTrue(false === $testChain->registerTask());
        foreach ($files as $f) {
            if (file_exists("/tmp/$f")) {
                unlink("/tmp/$f");
            }
            $testTask = new ReportingTask();
            $testTask->cliCommand('touch', "/tmp/$f");
            $testChain->addTask($testTask);
        }
        $firstTaskId = $testChain->registerChain();
        $this->assertTrue($firstTaskId !== null);
        $time = time()+120;
        Yii::$app->runAction('deferred/index', [$firstTaskId, $time, 1]);
        /** @var DeferredQueue $finishedTask */
        $finishedTask = DeferredQueue::loadModel($firstTaskId);
        $this->assertEquals(DeferredQueue::STATUS_SUCCESS_AND_NEXT, $finishedTask->status);
        $this->assertTrue(file_exists('/tmp/task201'));
    }

    public function testDeferredHelper()
    {
        if (true === file_exists('/tmp/301')) {
            unlink('/tmp/301');
        }
        $testTask = new ReportingTask();
        $testTask->cliCommand('touch', ['/tmp/301']);
        $testTask->registerTask();
        echo "Running queue with DeferredHelper\n";
        DeferredHelper::runImmediateTask($testTask->model()->id);
        sleep(2);
        $this->assertTrue(file_exists('/tmp/301'));
    }

    public function testQueueCompleteEventHandler()
    {
        $files = [
            'task401',
            'task402',
        ];
        $testChain = new ReportingChain();
        foreach ($files as $f) {
            if (file_exists("/tmp/$f")) {
                unlink("/tmp/$f");
            }
            $testTask = new ReportingTask();
            $testTask->cliCommand('touch', ["/tmp/$f"]);
            $testChain->addTask($testTask);
        }
        $firstTaskId = $testChain->registerChain();
        DeferredHelper::runImmediateTask($firstTaskId);
        sleep(2);
        $this->assertTrue(file_exists('/tmp/task401'));
        /** @var DeferredQueue $queue */
        $queue = DeferredQueue::findOne(['id' => $firstTaskId]);
        $process = new Process('pwd > /dev/null');
        $process->run();
        $queue->setProcess($process);
        $event = new DeferredQueueCompleteEvent($queue);
        QueueCompleteEventHandler::handleEvent($event);
        sleep(2);
        $this->assertTrue(file_exists('/tmp/task402'));
    }
}
