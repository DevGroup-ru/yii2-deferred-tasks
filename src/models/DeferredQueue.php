<?php

namespace DevGroup\DeferredTasks\models;

use \Cron\CronExpression;
use Symfony\Component\Process\Process;
use Yii;

/**
 * This is the model class for table "{{%deferred_queue}}".
 *
 * @property integer $id
 * @property integer $deferred_group_id
 * @property integer $user_id
 * @property string $initiated_date
 * @property integer $is_repeating_task
 * @property string $cron_expression
 * @property string $next_start
 * @property integer $status
 * @property string $last_run_date
 * @property string $console_route
 * @property string $cli_command
 * @property string $arguments
 * @property integer $notify_initiator
 * @property string $notify_roles
 * @property integer $email_notification
 */
class DeferredQueue extends \yii\db\ActiveRecord
{
    const STATUS_DISABLED = 0;
    const STATUS_SCHEDULED = 1;
    const STATUS_RUNNING = 2;
    const STATUS_FAILED = 3;

    /** @var null|Process  */
    private $process = null;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%deferred_queue}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['deferred_group_id', 'user_id', 'is_repeating_task', 'status', 'notify_initiator', 'email_notification'], 'integer'],
            [['initiated_date', 'next_start', 'last_run_date'], 'safe'],
            [['cron_expression', 'console_route', 'cli_command', 'notify_roles'], 'string', 'max' => 255],
            [['arguments'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'deferred_group_id' => Yii::t('app', 'Deferred group ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'initiated_date' => Yii::t('app', 'Initiated date'),
            'is_repeating_task' => Yii::t('app', 'Is repeating task'),
            'cron_expression' => Yii::t('app', 'Cron expression'),
            'next_start' => Yii::t('app', 'Next start date'),
            'status' => Yii::t('app', 'Status'),
            'last_run_date' => Yii::t('app', 'Last run date'),
            'console_route' => Yii::t('app', 'Console route'),
            'cli_command' => Yii::t('app', 'Cli command'),
            'arguments' => Yii::t('app', 'Command arguments'),
            'notify_initiator' => Yii::t('app', 'Notify initiator'),
            'notify_roles' => Yii::t('app', 'Notify roles'),
            'email_notification' => Yii::t('app', 'Email notification'),
        ];
    }

    /**
     * Returns corresponding DeferredGroup model instance
     * @return DeferredGroup|null
     */
    public function getGroup()
    {
        return
            isset($this->deferred_group_id)
            ? DeferredGroup::findById($this->deferred_group_id)
            : null;
    }

    /**
     * @return null|Process Returns process object
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @param Process $process sets process object
     */
    public function setProcess(&$process)
    {
        $this->process = $process;
    }

    /**
     * Schedules next launch or deletes queue item if it is not repeatable
     *
     * @return bool Result of completing
     * @throws \Exception
     */
    public function complete()
    {
        $this->last_run_date = date('Y-m-d H:i:s', time());

        if ($this->is_repeating_task && !empty($this->cron_expression)) {
            $this->status = DeferredQueue::STATUS_SCHEDULED;
            $cron = CronExpression::factory($this->cron_expression);
            $this->next_start = date('Y-m-d H:i:s', $cron->getNextRunDate()->getTimestamp());
            return $this->save();
        } else {
            return $this->delete() !== false;
        }
    }

    /**
     * @return bool returns true if command was successfully executed
     */
    public function isSuccess()
    {
        if (isset($this->process)) {
            return $this->process->getExitCode() === 0;
        } else {
            return false;
        }
    }

    /**
     * @param integer $currentTime
     * @return DeferredQueue[]
     */
    public static function getNextTasks($currentTime)
    {
        $currentTime = date("Y-m-d H:i:s", $currentTime);

        return DeferredQueue::find()
            ->where(['status' => DeferredQueue::STATUS_SCHEDULED])
            ->andWhere('next_start <= :next_start', [':next_start' => $currentTime])
            ->orderBy(['id'=>SORT_ASC])
            ->all();
    }
}
