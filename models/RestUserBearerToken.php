<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2023 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use humhub\libs\DbDateValidator;
use humhub\modules\user\models\User;

/**
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property string $expiration
 *
 * @property string $userIds
 *
 * @property-read User $user
 */
class RestUserBearerToken extends ActiveRecord
{
    public $expirationTime;

    public $newToken;

    public static function tableName()
    {
        return 'rest_user_bearer_tokens';
    }

    public function rules()
    {
        return [
            [['user_id', 'userIds', 'expiration'], 'required'],
            [['expiration'], DbDateValidator::class, 'timeAttribute' => 'expirationTime'],
            [['expirationTime'], 'date', 'type' => 'time', 'format' => Yii::$app->formatter->isShowMeridiem() ? 'h:mm a' : 'php:H:i'],
            [['userIds'], 'each', 'rule' => ['integer']],
            [['user_id'], 'unique', 'message' => Yii::t('RestModule.base', '{attribute} is already in use!')],
            [['user_id'], 'exist', 'targetRelation' => 'user', 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'user_id' => Yii::t('RestModule.base', 'User'),
            'userIds' => Yii::t('RestModule.base', 'User'),
            'token' => Yii::t('RestModule.base', 'Token'),
            'expiration' => Yii::t('RestModule.base', 'Expiration'),
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->newToken = Yii::$app->security->generateRandomString(86);
            $this->token = hash('sha256', (string) $this->newToken);
        }

        return parent::beforeSave($insert);
    }

    public function afterValidate()
    {
        parent::afterValidate();

        if ($this->hasErrors('user_id')) {
            $this->addError('userIds', $this->getFirstError('user_id'));
        }
    }

    public function setUserIds($userIds)
    {
        $this->user_id = ArrayHelper::getValue($userIds, 0);

        //TODO: remove after humhub 1.18 release
        if (!is_numeric($this->user_id)) {
            $this->user_id = User::find()
                ->select('id')
                ->where(['guid' => $this->user_id])
                ->scalar();
        }
    }

    public function getUserIds()
    {
        return [ArrayHelper::getValue($this->user, 'id')];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
