<?php

namespace DevGroup\DeferredTasks\commands;

use \duncan3dc\Helpers\Fork;
use DevGroup\DeferredTasks\events\DeferredGroupEvent;
use DevGroup\DeferredTasks\events\DeferredQueueCompleteEvent;
use DevGroup\DeferredTasks\events\DeferredQueueEvent;
use DevGroup\DeferredTasks\events\DeferredQueueGroupCompleteEvent;
use DevGroup\DeferredTasks\models\DeferredGroup;
use DevGroup\DeferredTasks\models\DeferredQueue;
use Symfony\Component\Process\ProcessBuilder;
use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Class DeferredController is main console controller class for running deferred tasks
 *
 * @package DevGroup\DeferredTasks\commands
 */
class DeferredController extends Controller
{
    private $forceNoParallel = false;
    /**
     * Runs all deferred commands
     */
    public function actionIndex($currentTime = null, $forceNoParallel = 0)
    {
        $currentTime = $currentTime ? intval($currentTime) : time();
        $this->forceNoParallel = intval($forceNoParallel) === 1;

        // acquire lock for all queue
        if ($this->getMutex()->acquire('DeferredQueueSelect') === false) {
            // another process is fetching deferred queue
            // that means your machine is too slow or SQL server is overloaded
            return 0;
        }

        $this->stdout("Getting queue\n", Console::FG_GREEN);

        // get scheduled queue
        /** @var DeferredQueue[] $queue */
        $queue = DeferredQueue::getNextTasks($currentTime);

        if (count($queue) === 0) {
            $this->getMutex()->release('DeferredQueueSelect');
            $this->stdout("No tasks to run\n", Console::FG_GREEN);
            return 0;
        }

        // group queue
        $grouppedQueue = [];
        $ids = [];
        foreach ($queue as $index => $item) {
            $ids[] = $item->id;

            $itemCanBeAdded = true;

            if (isset($grouppedQueue[$item->deferred_group_id]) === false) {
                // lock group so no new tasks will be run before this batch is ended
                // not applied to zero group(no group)
                if ($item->deferred_group_id > 0) {
                    $itemCanBeAdded = $this->getMutex()->acquire('DeferredQueueGroup:' . $item->deferred_group_id);
                    $grouppedQueue[$item->deferred_group_id] = [];
                }
            }
            if ($itemCanBeAdded === true) {
                $grouppedQueue[$item->deferred_group_id][] = $item;
            }


        }
        $queue = null;

        // lock queue elements
        Yii::$app->db->createCommand()
            ->update(
                DeferredQueue::tableName(),
                ['status' => DeferredQueue::STATUS_RUNNING],
                ['id' => $ids]
            )
            ->execute();

        // release DeferredQueueSelect lock
        $this->getMutex()->release('DeferredQueueSelect');

        // ok, now we can process groups
        if ($this->canRunInParallel() && $this->forceNoParallel === false) {

            $fork = new Fork;

            foreach ($grouppedQueue as $groupId => $items) {
                $fork->call(function() use($groupId, $items){
                    $this->processGroup($groupId, $items);
                }, [$groupId, $items]);
            }
            $fork->wait();

        } else {
            foreach ($grouppedQueue as $groupId => $items) {
                $this->processGroup($groupId, $items);
            }
        }
        $this->stdout("All tasks finished\n", Console::FG_GREEN);
        return 0;
    }

    /**
     * @return bool If process can run in parallel (php has needed extensions)
     */
    private function canRunInParallel()
    {
        return function_exists('pcntl_fork') && function_exists('shmop_open');
    }

    /**
     * @return boolean Releases main queue lock
     */
    public function actionReleaseQueueLock()
    {
        return $this->getMutex()->release('DeferredQueueSelect');
    }

    /**
     * Releases group lock
     * @param integer $id
     * @return mixed
     */
    public function actionReleaseGroupLock($id)
    {
        return $this->getMutex()->release('DeferredQueueGroup:' . $id);
    }

    /**
     * @param $group_id
     * @param DeferredQueue[]  $queue
     * @return DeferredQueue[]
     */
    private function processGroup($group_id, $queue)
    {
        $parallel_run_allowed = true;

        $group = DeferredGroup::findById($group_id);
        if (is_object($group) === true) {
            $parallel_run_allowed = boolval($group->allow_parallel_run);

            // check if group set to run only latest command
            if ($group->run_last_command_only) {
                $latestQueue = null;
                foreach ($queue as $item) {
                    if ($latestQueue === null) {
                        $latestQueue = $item;
                    }
                    if ($item->initiated_date >= $latestQueue->initiated_date) {
                        $latestQueue = $item;
                    }
                }
                foreach ($queue as $item) {
                    if ($item->id !== $latestQueue->id) {
                        $item->complete();
                    }
                }

                $queue = [$latestQueue];
            }
        }

        if ($this->forceNoParallel === true) {
            $parallel_run_allowed = false;
        }

        if ($parallel_run_allowed === true && $group_id > 0) {
            $this->getMutex()->release('DeferredQueueGroup:' . $group_id);
        }

        $this->trigger('deferred-queue-group-started', new DeferredGroupEvent($group_id));

        foreach ($queue as &$item) {
            $process = $this->runQueueItem($item);

            $this->trigger('deferred-queue-item-started', new DeferredQueueEvent($item->deferred_group_id, $item->id));

            $this->stdout("Executing process -> " . $process->getCommandLine() . "\n", Console::FG_YELLOW);
            $item->setProcess($process);

            if ($parallel_run_allowed === true) {
                $process->start();
            } else {
                $process->run();

                $this->immediateNotification($group, $item);
            }


        }

        if ($parallel_run_allowed === true) {
            foreach ($queue as &$item) {
                $item->getProcess()->wait();
                $this->immediateNotification($group, $item);
            }

            $this->stdout("All processes complete\n", Console::FG_YELLOW);
        } elseif ($group_id > 0) {
            $this->getMutex()->release('DeferredQueueGroup:' . $group_id);
        }

        $this->grouppedNotification($group, $queue);

        return $queue;
    }

    /**
     * Sends groupped notification if needed
     * @param DeferredGroup $group
     * @param DeferredQueue[] $queue
     */
    private function grouppedNotification(&$group, &$queue)
    {
        if (is_object($group) === false) {
            return;
        }
        if (intval($group->group_notifications) === 0) {
            return;
        }

        foreach ($queue as &$item) {
            $item->complete();
        }

        $this->trigger(
            'deferred-queue-group-complete',
            new DeferredQueueGroupCompleteEvent($queue, $group)
        );
    }

    /**
     * Sends immediate notification if needed
     * @param DeferredGroup $group
     * @param DeferredQueue $item
     */
    private function immediateNotification(&$group, &$item)
    {
        $item->complete();

        $immediateNotification = false;

        if (is_object($group) === true) {
            if (intval($group->group_notifications) === 0) {
                $immediateNotification = true;
            }
        } else {
            $immediateNotification = true;
        }
        if ($immediateNotification === true) {
            $this->trigger(
                'deferred-queue-complete',
                new DeferredQueueCompleteEvent($item)
            );
        }
    }

    /**
     * @param DeferredQueue $item
     * @return \Symfony\Component\Process\Process
     */
    private function runQueueItem($item)
    {

        $command = new ProcessBuilder();
        $command->setWorkingDirectory($this->getWorkingDirectory());

        if (empty($item->cli_command) === false) {
            $command->setPrefix($item->cli_command);
        } else {
            $command
                ->setPrefix('./yii')
                ->add($item->console_route);
        }
        if (empty($item->command_arguments) === false) {
            $args = explode("\n", $item->command_arguments);
            foreach ($args as $arg) {
                $arg = trim($arg);
                if (!empty($arg)) {
                    $command->add($arg);
                }
            }
        }

        $process = $command->getProcess();

        return $process;
    }

    /**
     * @return \yii\mutex\Mutex
     */
    protected function getMutex()
    {
        return Yii::$app->get('mutex');
    }

    /**
     * @return string Working directory for all commands
     */
    protected function getWorkingDirectory()
    {
        return Yii::getAlias('@app');
    }
}
