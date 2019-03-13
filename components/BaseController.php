<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\components;

use Exception;
use Firebase\JWT\JWT;
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
        $user = $this->authWithJwt();
        if (! $user) {
            throw new HttpException('401', 'Invalid Token!');
        }

        Yii::$app->user->login(User::findOne(['id' => $user->id]));

        return parent::beforeAction($action);
    }

    protected function authWithJwt()
    {
        $authHeader = Yii::$app->request->getHeaders()->get('Authorization');

        if (!empty($authHeader) && preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            $token = $matches[1];
            try{
                $valid_data = JWT::decode($token, $this->getApiKey(), ['HS512']);
                return $valid_data->data;
            }catch(Exception $e){
                throw new HttpException(401, $e->getMessage());
            }
        }

        return null;
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
     * @param ActiveQuery $query
     * @param int $limit
     * @return Pagination the pagination
     */
    protected function handlePagination(ActiveQuery $query, $limit = 100)
    {
        $limit = (int) Yii::$app->request->get('limit', $limit);
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

        return $pagination;
    }

    protected function returnPagination(ActiveQuery $query, Pagination $pagination, $data)
    {
        return [
            'total' => $pagination->totalCount,
            'page' => $pagination->getPage() + 1,
            'pages' => $pagination->getPageCount(),
            'links' => $pagination->getLinks(),
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