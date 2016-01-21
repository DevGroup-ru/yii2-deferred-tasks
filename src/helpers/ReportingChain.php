<?php
namespace DevGroup\DeferredTasks\helpers;

use Yii;

/**
 * Class ReportingChain helps you to create chain of dependent ReportingTasks, which uses common output file.
 * Each next task will be fired if previous one has finished with success.
 * So this way tou can see all progress in one window.
 *
 * Usage:
 *  $chain = new ReportingChain();
 *  $task1 = new ReportingTask();
 *  $task1->cliCommand($command1, $arguments1);
 *  $task2 = new ReportingTask();
 *  $task2->cliCommand($command2, $arguments2);
 *  $chain->addTask($task1);
 *  $chain->addTask($task2);
 *  if (null !== $firstTaskId = $chain->registerChain()) {
 *    DeferredHelper::runImmediateTask($firstTaskId);
 *    Yii::$app->response->format = Response::FORMAT_JSON;
 *    return [
 *       'queueItemId' => $firstTaskId,
 *    ];
 *  } else {
 *    throw new ServerErrorHttpException("Unable to start the chain.");
 *  }
 *
 * @package DevGroup\DeferredTasks\helpers
 */
class ReportingChain extends OnetimeTask
{
    /**
     * Common chain reporting filename
     * @var string
     */
    public $output_file;
    /**
     * Chained tasks array
     * @var ReportingTask[]
     */
    private $chainedQueue = [];

    /**
     * You should not use this method in chains.
     * Use ReportingChain::registerChain() instead.
     *
     * @return bool
     */
    public function registerTask()
    {
        return false;
    }

    public function __construct($attributes=[])
    {
        parent::__construct($attributes);
        $this->model = null;
        $this->output_file = Yii::getAlias('@runtime') . '/deferred-task-'.uniqid();
        touch($this->output_file);
        chmod($this->output_file, 0777);
    }

    /**
     * Adds new chained task into chain
     *
     * @param ReportingTask $model
     */
    public function addTask(ReportingTask $model)
    {
        $this->chainedQueue[] = $model;
    }

    /**
     * Registers newly created chain
     *
     * @return int|null first chained ReportingTask id
     */
    public function registerChain()
    {
        /** @var ReportingTask $parentTask */
        $parentTask = null;
        $firstTask = true;
        $firstTaskId = null;
        foreach ($this->chainedQueue as $task) {
            /** @var ReportingTask $task */
            $task->model->output_file = $this->output_file;
            $task->model->delete_after_run = false;
            if ($task->registerTask()) {
                if (true === $firstTask) {
                    $firstTaskId = $task->model->id;
                    $firstTask = false;
                }
                if (null !== $parentTask) {
                    $parentTask->model->next_task_id = $task->model->id;
                    $parentTask->model->save('next_task_id');
                }
                $parentTask = $task;

            }
        }
        return $firstTaskId;
    }
}