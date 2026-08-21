<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\models;

use Yii;
use yii\base\Model;
use humhub\modules\rest\Module;

class ConfigureForm extends Model
{
    public $enableJwtAuth;

    public $enableBasicAuth;

    public $enableBearerAuth;

    public $enableQueryParamAuth;

    /**
     * @var bool whether API requests may be authenticated by the regular HumHub browser
     * session, see {@see \humhub\modules\rest\components\auth\SessionAuth} for the full
     * security contract (CSRF requirement, gate enforcement, allowlist bypass, token
     * precedence).
     *
     * Default DISABLED, like every other auth method: a module update must never silently
     * open a new authentication surface. The dev/Vue-islands instance enables it explicitly
     * in the admin config form.
     */
    public $enableSessionAuth;

    public $enabledForAllUsers;

    public $enabledUsers;

    public $apiModules;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['enableJwtAuth', 'enableBasicAuth', 'enableBearerAuth', 'enableQueryParamAuth', 'enableSessionAuth', 'enabledForAllUsers'], 'boolean'],
            [['enabledUsers', 'apiModules'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'enableJwtAuth' => Yii::t('RestModule.base', 'Allow JWT Authentication'),
            'enableBasicAuth' => Yii::t('RestModule.base', 'Allow HTTP Basic Authentication'),
            'enableBearerAuth' => Yii::t('RestModule.base', 'Allow Bearer Authentication'),
            'enableQueryParamAuth' => Yii::t('RestModule.base', 'Allow Query Param Bearer Authentication'),
            'enableSessionAuth' => Yii::t('RestModule.base', 'Allow Session Authentication'),
            'enabledForAllUsers' => Yii::t('RestModule.base', 'Enabled for all registered users'),
        ];
    }

    public function attributeHints()
    {
        return [
            'enableSessionAuth' => 'Disabled by default. Allows requests carrying a valid, logged-in HumHub browser session to use the API without a token. Modifying requests (POST/PUT/PATCH/DELETE) additionally require the CSRF token. Not restricted by the user list below — a session grants nothing beyond what the same user can already do in the web interface.',
            'enabledForAllUsers' => 'Please note, it is not recommended to enable the API for all users yet.<br/> This option affects JWT and HTTP Basic Authentication methods only.',
            'enabledUsers' => 'This option affects JWT and HTTP Basic Authentication methods only.',
        ];
    }

    public function loadSettings()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('rest');

        $settings = $module->settings;

        $this->enableJwtAuth = (bool)$settings->get('enableJwtAuth');
        $this->enableBasicAuth = (bool)$settings->get('enableBasicAuth');
        $this->enableBearerAuth = (bool)$settings->get('enableBearerAuth');
        $this->enableQueryParamAuth = (bool)$settings->get('enableQueryParamAuth');
        $this->enableSessionAuth = (bool)$settings->get('enableSessionAuth');
        $this->enabledForAllUsers = (bool)$settings->get('enabledForAllUsers');
        $this->enabledUsers = (array)$settings->getSerialized('enabledUsers');

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

        if (!$this->enableBearerAuth) {
            $this->enableQueryParamAuth = false;
        }

        $module->settings->set('enableJwtAuth', (bool)$this->enableJwtAuth);
        $module->settings->set('enableBasicAuth', (bool)$this->enableBasicAuth);
        $module->settings->set('enableBearerAuth', (bool)$this->enableBearerAuth);
        $module->settings->set('enableQueryParamAuth', (bool)$this->enableQueryParamAuth);
        $module->settings->set('enableSessionAuth', (bool)$this->enableSessionAuth);
        $module->settings->set('enabledForAllUsers', $this->enabledForAllUsers);
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
        $config = new static();
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
