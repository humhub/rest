<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest;

use humhub\components\bootstrap\ModuleAutoLoader;
use humhub\components\Module as BaseModule;
use Yii;
use yii\base\Event;
use yii\helpers\Url;

class Module extends BaseModule
{
    /**
     * @event triggered before a request (inside event Application::EVENT_BEFORE_REQUEST of this Module)
     */
    public const EVENT_REST_API_ADD_RULES = 'restApiAddRules';

    /**
     * @var string Prefix for REST API endpoint URLs
     */
    public const API_URL_PREFIX = 'api/v1/';

    /**
     * @inheritdoc
     */
    public function getConfigUrl()
    {
        return Url::to(['/rest/admin/index']);
    }

    /**
     * Add REST API endpoint rules
     *
     * @param array $rules
     * @param string $moduleId Provide module id if you want to make if disabled from settings of the module "REST API"
     */
    public function addRules($rules, $moduleId = null)
    {
        if ($moduleId !== null && !$this->isActiveModule($moduleId)) {
            return;
        }

        foreach ($rules as $r => $rule) {
            if (isset($rule['pattern'])) {
                $rules[$r]['pattern'] = self::API_URL_PREFIX . ltrim($rule['pattern'], '/');
            }
        }

        Yii::$app->urlManager->addRules($rules);
    }

    /**
     * Get enabled modules which have event to add REST API endpoints
     *
     * @return BaseModule[]
     */
    public function getModulesWithRestApi()
    {
        $enabledModules = Yii::$app->moduleManager->getEnabledModules();

        $restApiClass = Module::class;
        $restApiEvent = Module::EVENT_REST_API_ADD_RULES;
        $restApiModules = [];

        foreach ($enabledModules as $enabledModule) {
            /* @var BaseModule $enabledModule */
            $moduleConfigFilePath = $enabledModule->getBasePath() . DIRECTORY_SEPARATOR . ModuleAutoLoader::CONFIGURATION_FILE;
            if (!file_exists($moduleConfigFilePath)) {
                continue;
            }
            $moduleConfig = require $moduleConfigFilePath;
            if (empty($moduleConfig['events'])) {
                continue;
            }

            foreach ($moduleConfig['events'] as $event) {
                if ((isset($event['class'], $event['event']) && $event['class'] == $restApiClass && $event['event'] == $restApiEvent)
                    || (isset($event[0], $event[1]) && $event[0] == $restApiClass && $event[1] == $restApiEvent)) {
                    $restApiModules[] = $enabledModule;
                    break;
                }
            }
        }

        return $restApiModules;
    }

    /**
     * Check if the module is active for additional REST API endpoins
     *
     * @param string $moduleId
     * @return bool
     */
    public function isActiveModule($moduleId)
    {
        $apiModules = (array)$this->settings->getSerialized('apiModules');

        return !isset($apiModules[$moduleId]) || $apiModules[$moduleId];
    }

    public function beforeAction($action)
    {
        Yii::$app->on('twofa.beforeCheck', function (Event $event) use ($action) {
            $event->handled = $action->controller->id !== 'admin-user';
        });

        return parent::beforeAction($action);
    }
}
