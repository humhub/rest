<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\components;

use Yii;
use yii\data\Pagination;
use yii\db\ActiveQuery;
use yii\web\HttpException;

use humhub\components\Controller;
use humhub\modules\user\models\User;
use humhub\modules\rest\models\ApiUser;



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
        $req = Yii::$app->request;
        //parse_str($req->queryString);
        /* grab header - custom DF */
        $headers = $req->headers;
        $access_token = $headers->get('Authorization');
        $access_token = explode("Bearer ",$access_token)[1];
        if (!isset($access_token)) {
            throw new UnauthorizedHttpException('Access unavailable without access_token.', 401);
        }
        if (ApiUser::findIdentityByAccessToken($access_token)) {
            return true;
        }
        return false;
    }


    /**
     * Handles pagination
     *
     * @return Pagination the pagination
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

        return $pagination;
    }

    protected function returnPagination(ActiveQuery $query, Pagination $pagination, $data)
    {
        return [
            'total' => $pagination->totalCount,
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