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
 * @todo Change PHPUnit to Codeception
 * @todo change data/test.xml to php fixture and remove there hardcode /tmp/ dir to sys_get_temp_dir()
 */
class DeferredControllerTest extends \PHPUnit_Extensions_Database_TestCase
{

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
        $allFiles = [
            'task3',
            'task4',
            'task5',
            'task6',
            'task7',
            'task8',
            'task9',
            'task10',
            'task11',
            'task91',
            'task201',
            'task202',
            '301',
            'task401',
            'task402',
        ];
        foreach ($allFiles as $f) {
            $filename = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $f;
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
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
        $this->assertFalse($disabledTaskExists);
        $this->assertFalse($futureTaskExists);

    }

    public function testRunProcesses()
    {
        $time = mktime(19, 40, 0, 5, 19, 2015);
        Yii::$app->runAction('deferred/index', ['0', $time, 1]);
        $this->assertFileExists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'task3');
        $this->assertFileExists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'task4');
        $this->assertFileNotExists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'task5');
        $this->assertFileNotExists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'task6');
        $this->assertFileExists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'task7');
        $this->assertFileExists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'task8');
        $this->assertFileExists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'task9');
        $this->assertFileExists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'task10');
        $this->assertFileExists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'task11');
    }

    public function testRegister()
    {
        $task = new OnetimeTask();
        $task->cliCommand('touch', [sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'task91']);

        $this->assertTrue($task->registerTask());
        $time = time()+120;
        echo "Running $time = " . date("Y-m-d H:i:s", $time) . "\n";

        $this->assertInstanceOf(DeferredQueue::className(), $task->model());

        Yii::$app->runAction('deferred/index', [0, $time, 1]);

        echo "Checking\n";
        $this->assertFileExists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'task91');
    }

    public function testReportingChain()
    {
        $files = [
            'task201',
            'task202',
        ];
        $testChain = new ReportingChain();
        $this->assertFalse($testChain->registerTask());
        foreach ($files as $f) {
            $testTask = new ReportingTask();
            $testTask->cliCommand('touch', sys_get_temp_dir() . DIRECTORY_SEPARATOR . $f);
            $testChain->addTask($testTask);
        }
        $firstTaskId = $testChain->registerChain();
        $this->assertNotNull($firstTaskId);
        $time = time()+120;
        Yii::$app->runAction('deferred/index', [$firstTaskId, $time, 1]);
        /** @var DeferredQueue $finishedTask */
        $finishedTask = DeferredQueue::loadModel($firstTaskId);
        $this->assertEquals(DeferredQueue::STATUS_SUCCESS_AND_NEXT, $finishedTask->status);
        $this->assertFileExists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'task201');
    }

    public function testDeferredHelper()
    {
        $testTask = new ReportingTask();
        $testTask->cliCommand('touch', [sys_get_temp_dir() . DIRECTORY_SEPARATOR . '301']);
        $testTask->registerTask();
        echo "Running queue with DeferredHelper\n";
        DeferredHelper::runImmediateTask($testTask->model()->id);
        sleep(2);
        $this->assertFileExists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . '301');
    }

    public function testQueueCompleteEventHandler()
    {
        $files = [
            'task401',
            'task402',
        ];
        $testChain = new ReportingChain();
        foreach ($files as $f) {
            $testTask = new ReportingTask();
            $testTask->cliCommand('touch', [sys_get_temp_dir() . DIRECTORY_SEPARATOR . $f]);
            $testChain->addTask($testTask);
        }
        $firstTaskId = $testChain->registerChain();
        DeferredHelper::runImmediateTask($firstTaskId);
        sleep(2);
        $this->assertFileExists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'task401');
        /** @var DeferredQueue $queue */
        $queue = DeferredQueue::findOne(['id' => $firstTaskId]);
        $process = new Process('pwd > /dev/null');
        $process->run();
        $queue->setProcess($process);
        $event = new DeferredQueueCompleteEvent($queue);
        QueueCompleteEventHandler::handleEvent($event);
        sleep(2);
        $this->assertFileExists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'task402');
    }
}
