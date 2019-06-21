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

}