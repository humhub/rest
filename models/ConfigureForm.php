<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\models;

use humhub\modules\rest\Module;
use Yii;
use yii\base\Model;

class ConfigureForm extends Model
{

    public $enabledForAllUsers;

    public $enabledUsers;

    public $jwtKey;

    public $jwtExpire;

    public $enableBasicAuth;


    public $enableBearerAuth;


    public $enableQueryParamAuth;

    public $apiModules;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['jwtKey'], 'string', 'min' => 32, 'max' => 128],
            [['enabledUsers', 'apiModules'], 'safe'],
            [['enabledForAllUsers', 'enableBasicAuth', 'enableBearerAuth', 'enableQueryParamAuth'], 'boolean'],
            [['jwtExpire'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'jwtKey' => Yii::t('RestModule.base', 'JWT Key'),
            'jwtExpire' => Yii::t('RestModule.base','JWT Token Expiration'),
            'enabledForAllUsers' => Yii::t('RestModule.base', 'Enabled for all registered users'),
            'enableBasicAuth' => Yii::t('RestModule.base','Allow HTTP Basic Authentication'),
            'enableBearerAuth' => Yii::t('RestModule.base','Allow Bearer Authentication'),
            'enableQueryParamAuth' => Yii::t('RestModule.base','Allow Query Param Bearer Authentication'),
            'apiModules' => Yii::t('RestModule.base', 'Active additional REST API endpoints from the modules'),
        ];
    }

    /**
     * @inheritdoc
     */
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
        $this->enableBasicAuth = (boolean)$settings->get('enableBasicAuth');
        $this->enableBearerAuth = (boolean)$settings->get('enableBearerAuth');
        $this->enableQueryParamAuth = (boolean)$settings->get('enableQueryParamAuth');

        foreach ($module->getModulesWithRestApi() as $apiModule) {
            if ($module->isActiveModule($apiModule->id)) {
                $this->apiModules[] = $apiModule->id;
            }
        }

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
        $module->settings->set('enableBasicAuth', (boolean)$this->enableBasicAuth);
        $module->settings->set('enableBearerAuth', (boolean)$this->enableBearerAuth);
        $module->settings->set('enableQueryParamAuth', (boolean)$this->enableQueryParamAuth);
        $module->settings->setSerialized('enabledUsers', (array)$this->enabledUsers);

        $apiModules = [];
        foreach ($module->getModulesWithRestApi() as $apiModule) {
            $apiModules[$apiModule->id] = is_array($this->apiModules) && in_array($apiModule->id, $this->apiModules);
        }
        $module->settings->setSerialized('apiModules', $apiModules);

        return true;
    }

    public static function getInstance()
    {
        $config = new static;
        $config->loadSettings();

        return $config;
    }

    /**
     * Get options of modules with REST API endpoints
     *
     * @return array
     */
    public function getApiModuleOptions()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('rest');

        $options = [];
        foreach ($module->getModulesWithRestApi() as $apiModule) {
            $options[$apiModule->id] = $apiModule->getName();
        }

        return $options;
    }

}
