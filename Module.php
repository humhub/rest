<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest;

use humhub\components\UrlManager;
use humhub\modules\rest\models\ConfigureForm;
use Yii;
use yii\helpers\Url;
use yii\web\HttpException;
use yii\web\JsonParser;

class Module extends \humhub\components\Module
{

    /**
     * @event triggered before a request (inside event Application::EVENT_BEFORE_REQUEST of this Module)
     */
    const EVENT_REST_API_ADD_RULES = 'restApiAddRules';

    /**
     * @var string Prefix for REST API endpoint URLs
     */
    const API_URL_PREFIX = 'api/v1/';

    /**
     * @inheritdoc
     */
    public $resourcesPath = 'resources';


    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        Yii::$app->response->format = 'json';

        Yii::$app->request->setBodyParams(null);
        Yii::$app->request->parsers['application/json'] = JsonParser::class;

        return parent::beforeAction($action);
    }

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
     */
    public function addRules($rules)
    {
        foreach ($rules as $r => $rule) {
            if (isset($rule['pattern'])) {
                $rules[$r]['pattern'] = self::API_URL_PREFIX . ltrim($rule['pattern'], '/');
            }
        }

        Yii::$app->urlManager->addRules($rules);
    }

}