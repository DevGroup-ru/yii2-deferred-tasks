<?php

namespace DevGroup\DeferredTasks\events;


use DevGroup\DeferredTasks\models\DeferredGroup;
use DevGroup\DeferredTasks\models\DeferredQueue;

class DeferredQueueGroupCompleteEvent extends \yii\base\Event
{
    /** @var DeferredQueue[] */
    public $queues;

    /** @var DeferredGroup */
    public $group;

    /** @var boolean */
    public $overallStatus;

    /**
     * @inheritdoc
     * @param DeferredQueue[] $queues
     * @param DeferredGroup $group
     * @param array $config
     */
    public function __construct(&$queues, &$group, $config=[])
    {
        parent::__construct($config);
        $this->queues = $queues;
        $this->group = $group;

        $this->overallStatus = true;

        foreach ($this->queues as $item) {
            $this->overallStatus = $this->overallStatus && ($item->getProcess()->getExitCode()===0);
        }
    }
}
