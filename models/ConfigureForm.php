<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\models;

use Yii;

class ConfigureForm extends \yii\base\Model
{

    public $apiKey;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['apiKey'], 'string', 'min' => 10, 'max' => 200]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'apiKey' => Yii::t('RestModule.base', 'API key'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [];
    }

    public function loadSettings()
    {
        $settings = Yii::$app->getModule('rest')->settings;
        $this->apiKey = $settings->get('apiKey');
        return true;
    }

    public function saveSettings()
    {
        $settings = Yii::$app->getModule('rest')->settings;
        $settings->set('apiKey', $this->apiKey);
        return true;
    }

}
