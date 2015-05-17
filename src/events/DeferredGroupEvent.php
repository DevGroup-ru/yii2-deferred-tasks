<?php

namespace DevGroup\DeferredTasks\events;

class DeferredGroupEvent extends \yii\base\Event
{
    public $groupId;

    /**
     * @inheritdoc
     * @param integer|string $groupId
     * @param array $config
     */
    public function __construct($groupId, $config = [])
    {
        parent::__construct($config);
        $this->groupId = $groupId;
    }
}
