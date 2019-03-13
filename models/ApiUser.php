<?php

namespace humhub\modules\rest\models;
use Yii;

/**
 * This is the model class for table "api_user".
 *
 * @property integer $id
 * @property string $client
 * @property string $api_key
 * @property boolean $active
 */
class ApiUser extends \yii\db\ActiveRecord 
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'api_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['client', 'api_key'], 'required'],
            [['active'], 'boolean'],
            [['client'], 'string', 'max' => 255],
            [['api_key'], 'string', 'max' => 32]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'client' => 'Client',
            'api_key' => 'Api Key',
            'active' => 'Active',
        ];
    }

    /**
     * Implements authentication of the user api_key
     * @param String $token
     * @return mixed
     */
    public static function findIdentityByAccessToken($token)
    {
        return static::findOne(['api_key' => $token, 'active' => 1]);
    }

}
