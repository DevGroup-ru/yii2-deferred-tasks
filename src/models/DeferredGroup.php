<?php

namespace DevGroup\DeferredTasks\models;

use Yii;
use yii\caching\TagDependency;

/**
 * This is the model class for table "{{%deferred_group}}".
 *
 * @property integer $id
 * @property string $name
 * @property integer $allow_parallel_run
 * @property integer $run_last_command_only
 * @property integer $notify_initiator
 * @property string $notify_roles
 * @property integer $email_notification
 * @property integer $group_notifications
 */
class DeferredGroup extends \yii\db\ActiveRecord
{
    use \DevGroup\TagDependencyHelper\TagDependencyTrait;


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'CacheableActiveRecord' => [
                'class' => \DevGroup\TagDependencyHelper\CacheableActiveRecord::className(),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%deferred_group}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['allow_parallel_run', 'run_last_command_only', 'notify_initiator', 'email_notification', 'group_notifications'], 'integer'],
            [['name', 'notify_roles'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'allow_parallel_run' => Yii::t('app', 'Allow Parallel Run'),
            'run_last_command_only' => Yii::t('app', 'Run Last Command Only'),
            'notify_initiator' => Yii::t('app', 'Notify Initiator'),
            'notify_roles' => Yii::t('app', 'Notify Roles'),
            'email_notification' => Yii::t('app', 'Email Notification'),
            'group_notifications' => Yii::t('app', 'Group Notifications'),
        ];
    }

    /**
     * Finds DeferredGroup by ID with use of identity map and application cache
     * @param integer|string $id
     * @return DeferredGroup|null
     */
    public static function findById($id)
    {
        return self::loadModel(
            $id,
            false,
            true,
            86400,
            false,
            true
        );
    }
}
