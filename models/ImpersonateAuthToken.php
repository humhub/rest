<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2023 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use humhub\libs\DbDateValidator;
use humhub\modules\user\models\User;

/**
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property string $expiration
 *
 * @property-read User $user
 */
class ImpersonateAuthToken extends ActiveRecord
{
    public static function tableName()
    {
        return 'impersonate_auth_tokens';
    }

    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id'], 'unique'],
            [['user_id'], 'exist', 'targetRelation' => 'user', 'targetAttribute' => 'id'],
            [['expiration'], 'default', 'value' => function () {
                return new Expression('DATE_ADD(NOW(), INTERVAL 30 MINUTE)');
            }],
            [['token'], 'default', 'value' => function () {
                return 'impersonated-' . Yii::$app->security->generateRandomString(73);
            }],
        ];
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        self::deleteAll(['<=', 'expiration', new Expression('NOW()')]);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
