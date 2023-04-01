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
    public $enabledForAllUsers;

    public $enabledUsers;

    public $jwtKey;

    public $jwtExpire;

    public function rules()
    {
        return [
            [['jwtKey'], 'string', 'min' => 32, 'max' => 128],
            [['enabledUsers'], 'safe'],
            [['enabledForAllUsers', 'enableJwtAuth'], 'boolean'],
            [['jwtExpire'], 'integer']
        ];
    }

    public function attributeLabels()
    {
        return [
            'jwtKey' => Yii::t('RestModule.base', 'JWT Key'),
            'jwtExpire' => Yii::t('RestModule.base','JWT Token Expiration'),
            'enabledForAllUsers' => Yii::t('RestModule.base', 'Enabled for all registered users'),
            'enableJwtAuth' => Yii::t('RestModule.base','Allow JWT Authentication'),
        ];
    }

    public function attributeHints()
    {
        return [
            'jwtKey' => 'If empty, a random key is generated automatically.',
            'jwtExpire' => 'in seconds. 0 for no JWT token expiration.',
            'enabledForAllUsers' => 'Please note, it is not recommended to enable the API for all users yet.',
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

        $this->enabledForAllUsers = (boolean)$settings->get('enabledForAllUsers');
        $this->enabledUsers = (array)$settings->getSerialized('enabledUsers');
        $this->jwtExpire = (int)$settings->get('jwtExpire');

        return true;
    }

    public function saveSettings()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('rest');

        if (!$this->enableBearerAuth ) {
            $this->enableQueryParamAuth = false;
        }

        $module->settings->set('jwtExpire', (int)$this->jwtExpire);
        $module->settings->set('jwtKey', $this->jwtKey);
        $module->settings->set('enabledForAllUsers', $this->enabledForAllUsers);
        $module->settings->setSerialized('enabledUsers', (array)$this->enabledUsers);

        return true;
    }

    public static function getInstance()
    {
        $config = new static;
        $config->loadSettings();

        return $config;
    }
}
