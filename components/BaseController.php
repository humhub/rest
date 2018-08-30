<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\components;

use humhub\components\Controller;
use humhub\modules\rest\models\ConfigureForm;
use humhub\modules\user\models\User;
use Yii;
use yii\data\Pagination;
use yii\db\ActiveQuery;
use yii\web\HttpException;


/**
 * Class BaseController
 *
 * @package humhub\modules\rest\components
 */
abstract class BaseController extends Controller
{
    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;


    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!$this->auth()) {
            throw new HttpException('401', 'Invalid API Key!');
        }

        Yii::$app->user->login(User::findOne(['id' => 1]));

        return parent::beforeAction($action);
    }


    /**
     * Simple authentication using the specified API key
     *
     * @return bool authenticated
     * @throws HttpException
     */
    protected function auth()
    {
        $apiKey = $this->getApiKey();

        $authHeader = Yii::$app->request->getHeaders()->get('Authorization');

        // HttpBearer
        if (!empty($authHeader) && preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches) && $matches[1] == $apiKey) {
            return true;
        }

        // Api key as request parameter
        $keyParam = Yii::$app->request->get('key', Yii::$app->request->post('key'));
        if (!empty($keyParam) && $keyParam == $apiKey) {
            return true;
        }

        return false;
    }

    /**
     * Returns the configured API key
     *
     * @return string the API key
     * @throws HttpException when no api key is configured
     */
    protected function getApiKey()
    {
        $config = new ConfigureForm();
        $config->loadSettings();

        if (empty($config->apiKey)) {
            throw new HttpException('404', 'API disabled - No API KEY configured.');
        }

        return $config->apiKey;
    }

    /**
     * Handles pagination
     *
     * @return string the API key
     */
    protected function handlePagination(ActiveQuery $query)
    {
        $limit = (int) Yii::$app->request->get('limit', 100);
        $page = (int) Yii::$app->request->get('page', 1);

        if ($limit > 100) {
            $limit = 100;
        }

        $page--;

        $countQuery = clone $query;
        $pagination = new Pagination(['totalCount' => $countQuery->count()]);
        $pagination->setPage($page);
        $pagination->setPageSize($limit);

        $query->offset($pagination->offset);
        $query->limit($pagination->limit);
    }

    protected function returnPagination(ActiveQuery $query, $data)
    {
        return [
            'total' => $query->count(),
            'page' => 1,
            'results' => $data,
        ];
    }

    protected function returnError($statusCode = 400, $message = 'Invalid request', $additional = [])
    {
        Yii::$app->response->statusCode = $statusCode;
        return array_merge(['code' => $statusCode, 'message' => $message], $additional);
    }


    protected function returnSuccess($message = 'Request successful', $statusCode = 200, $additional = [])
    {
        Yii::$app->response->statusCode = $statusCode;
        return array_merge(['code' => $statusCode, 'message' => $message], $additional);
    }
}