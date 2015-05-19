<?php

namespace DevGroup\DeferredTasks\helpers;

use yii\base\Component;

/**
 * Class DeferredHelper is the main helper class for deferred tasks module.
 * DeferredHelper should be added as application component in both console and web configs.
 *
 * @package DevGroup\DeferredTasks\helpers
 */
class DeferredHelper extends Component
{
    /**
     * @var bool True if you don't want to run tasks in parallel mode even if you able to do it.
     */
    public $forceNoParallel = false;


}