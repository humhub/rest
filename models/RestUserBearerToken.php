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
 * @property string $userGuid
 *
 * @property-read User $user
 */
class RestUserBearerToken extends ActiveRecord
{
    public $expirationTime;

    public static function tableName()
    {
        return 'rest_user_bearer_tokens';
    }

    public function rules()
    {
        return [
            [['user_id', 'userGuid', 'expiration'], 'required'],
            [['expiration'], DbDateValidator::class, 'timeAttribute' => 'expirationTime'],
            [['expirationTime'], 'date', 'type' => 'time', 'format' => Yii::$app->formatter->isShowMeridiem() ? 'h:mm a' : 'php:H:i'],
            [['userGuid'], 'each', 'rule' => ['string', 'max' => 45]],
            [['user_id'], 'unique'],
            [['user_id'], 'exist', 'targetRelation' => 'user', 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'user_id' => Yii::t('RestModule.base','User'),
            'userGuid' => Yii::t('RestModule.base','User'),
            'token' => Yii::t('RestModule.base','Token'),
            'expiration' => Yii::t('RestModule.base','Expiration'),
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->token = Yii::$app->security->generateRandomString(86);
        }

        return parent::beforeSave($insert);
    }

    public function afterValidate()
    {
        parent::afterValidate();

        if ($this->hasErrors('user_id')) {
            $this->addError('userGuid', $this->getFirstError('user_id'));
        }
    }

    public function setUserGuid($guid)
    {
        $this->user_id = User::find()
            ->select('id')
            ->where(['guid' => $guid])
            ->scalar();
    }

    public function getUserGuid()
    {
        return [ArrayHelper::getValue($this->user, 'guid')];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
