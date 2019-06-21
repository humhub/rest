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
use humhub\modules\rest\controllers\auth\AuthController;
use humhub\modules\rest\models\ConfigureForm;
use humhub\modules\rest\Module;
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
    public static $moduleId = '';

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
        $config = ConfigureForm::getInstance();

        if ($user === null && !empty($config->enableBasicAuth)) {
            // Try login by username and password
            list($username, $password) = Yii::$app->request->getAuthCredentials();
            $user = AuthController::authByUserAndPassword($username, $password);
        }

        if ($user === null) {
            throw new HttpException('401', 'Invalid token!');
        }

        if (!$this->isUserEnabled($user)) {
            throw new HttpException('401', 'Invalid user!');
        }

        Yii::$app->user->login($user);

        return parent::beforeAction($action);
    }


    /**
     * Not supported
     *
     * @return array
     */
    public function actionNotSupported()
    {
        $module = static::$moduleId;
        return $this->returnError(404, "{$module} module does not installed. Please install or enable {$module} module to use this API");
    }


    /**
     * Authentication using JWT Bearer Header
     *
     * @return User|null
     * @throws HttpException
     */
    private function authWithJwt()
    {
        $authHeader = Yii::$app->request->getHeaders()->get('Authorization');

        /** @var Module $module */
        $module = Yii::$app->getModule('rest');

        if (!empty($authHeader) && preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            $token = $matches[1];
            try {
                $validData = JWT::decode($token, ConfigureForm::getInstance()->jwtKey, ['HS512']);

                if (!empty($validData->uid)) {
                    return User::find()->active()->andWhere(['user.id' => $validData->uid])->one();
                }

            } catch (Exception $e) {
                throw new HttpException(401, $e->getMessage());
            }
        }

        return null;
    }


    /**
     * Checks if users is allowed to use the Rest API
     *
     * @param User $user
     * @return bool
     */
    private function isUserEnabled(User $user)
    {

        $config = new ConfigureForm();
        $config->loadSettings();

        if (!empty($config->enabledForAllUsers)) {
            return true;
        }

        if (in_array($user->guid, (array)$config->enabledUsers)) {
            return true;
        }

        return false;
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
        $limit = (int)Yii::$app->request->get('limit', $limit);
        $page = (int)Yii::$app->request->get('page', 1);

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


    /**
     * Generates pagination response
     *
     * @param ActiveQuery $query
     * @param Pagination $pagination
     * @param $data array
     * @return array
     */
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


    /**
     * Generates error response
     *
     * @param int $statusCode
     * @param string $message
     * @param array $additional
     * @return array
     */
    protected function returnError($statusCode = 400, $message = 'Invalid request', $additional = [])
    {
        Yii::$app->response->statusCode = $statusCode;
        return array_merge(['code' => $statusCode, 'message' => $message], $additional);
    }


    /**
     * Generates success response
     *
     * @param string $message
     * @param int $statusCode
     * @param array $additional
     * @return array
     */
    protected function returnSuccess($message = 'Request successful', $statusCode = 200, $additional = [])
    {
        Yii::$app->response->statusCode = $statusCode;
        return array_merge(['code' => $statusCode, 'message' => $message], $additional);
    }
}