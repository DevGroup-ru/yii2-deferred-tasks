<?php

namespace DevGroup\DeferredTasks\events;

/**
 * Class DeferredQueueEvent is an event triggered when DeferredQueue is set to start
 * Event name is 'deferred-queue-item-started'.
 * The event is triggered on DeferredController (main console controller for running tasks).
 *
 * For adding event handlers use:
 *
 * ```
 * DeferredQueueEvent::on('DevGroup\DeferredTasks\commands\DeferredController', function($event) {});
 * ```
 *
 * @package DevGroup\DeferredTasks\events
 */
class DeferredQueueEvent extends \yii\base\Event
{
    public $groupId;
    public $queueId;

    /**
     * @inheritdoc
     * @param integer|string $groupId
     * @param integer|string $queueId
     * @param array $config
     */
    public function __construct($groupId, $queueId, $config=[]) {
        parent::__construct($config);
        $this->groupId = $groupId;
        $this->queueId = $queueId;
    }
}