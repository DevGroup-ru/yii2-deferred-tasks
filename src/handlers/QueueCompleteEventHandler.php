<?php
namespace DevGroup\DeferredTasks\handlers;

use DevGroup\DeferredTasks\events\DeferredQueueCompleteEvent;
use DevGroup\DeferredTasks\helpers\DeferredHelper;
use DevGroup\DeferredTasks\models\DeferredQueue;
use yii\base\Object;

class QueueCompleteEventHandler extends Object
{

    public static function handleEvent(DeferredQueueCompleteEvent $event)
    {
        $queue = $event->queue;
        if ($queue->status == DeferredQueue::STATUS_SUCCESS_AND_NEXT && $queue->next_task_id != 0) {
            DeferredHelper::runImmediateTask($queue->next_task_id);
        }
    }
}