<?php

namespace humhub\modules\rest\models;

use Yii;
use yii\base\Model;
use humhub\modules\user\models\Auth;
use humhub\modules\user\models\User;

class UserAuthForm extends Model
{
    public $userId;
    public $source;
    public $sourceId;

    public function rules()
    {
        return [
            [['userId', 'source', 'sourceId'], 'required'],
            [['source', 'sourceId'], 'string', 'max' => 255],
            [['userId'], 'integer'],
            [['userId'], 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'source' => Yii::t('RestModule.base', 'Source'),
            'source_id' => Yii::t('RestModule.base', 'Source ID'),
        ];
    }

    public function save()
    {
        if ($this->validate()) {
            $auth = new Auth();
            $auth->setAttributes([
                'user_id' => $this->userId,
                'source' => $this->source,
                'source_id' => $this->sourceId,
            ]);
            $auth->save();
        }

        return !$this->hasErrors();
    }
}
