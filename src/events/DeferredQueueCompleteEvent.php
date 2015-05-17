<?php

namespace DevGroup\DeferredTasks\events;


use DevGroup\DeferredTasks\models\DeferredGroup;
use DevGroup\DeferredTasks\models\DeferredQueue;

class DeferredQueueCompleteEvent extends DeferredQueueEvent
{
    /** @var boolean True if ok */
    public $success;
    /** @var DeferredQueue */
    public $queue;

    /** @var DeferredGroup|null */
    public $group;

    /**
     * @inheritdoc
     * @param DeferredQueue $queue
     * @param array $config
     */
    public function __construct(&$queue, $group = null, $config = [])
    {
        parent::__construct($queue->deferred_group_id, $queue->id, $config);
        $this->queue = $queue;
        $this->group = $group;
        $this->success = $this->queue->getProcess()->getExitCode() === 0;
    }
}
