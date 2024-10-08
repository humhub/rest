<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2023 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\models;

use humhub\modules\rest\Module;
use Yii;
use yii\base\Model;

class JwtAuthForm extends Model
{
    public $jwtKey;

    public $jwtExpire;

    public function rules()
    {
        return [
            [['jwtKey'], 'string', 'min' => 32, 'max' => 128],
            [['jwtExpire'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'jwtKey' => Yii::t('RestModule.base', 'JWT Key'),
            'jwtExpire' => Yii::t('RestModule.base', 'JWT Token Expiration'),
        ];
    }

    public function attributeHints()
    {
        return [
            'jwtKey' => 'If empty, a random key is generated automatically.',
            'jwtExpire' => 'in seconds. 0 for no JWT token expiration.',
        ];
    }

    public function loadSettings()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('rest');

        $settings = $module->settings;

        $this->jwtKey = $settings->get('jwtKey');
        if (empty($this->jwtKey)) {
            $settings->set('jwtKey', Yii::$app->security->generateRandomString(86));
            $this->jwtKey = $settings->get('jwtKey');
        }

        $this->jwtExpire = (int)$settings->get('jwtExpire');

        return true;
    }

    public function saveSettings()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('rest');

        $module->settings->set('jwtExpire', (int)$this->jwtExpire);
        $module->settings->set('jwtKey', $this->jwtKey);

        return true;
    }

    public static function getInstance()
    {
        $config = new static();
        $config->loadSettings();

        return $config;
    }
}
