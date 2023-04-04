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
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\helpers\ArrayHelper;
use yii\web\JsonParser;
use Firebase\JWT\JWT;
use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\content\models\Content;
use humhub\modules\rest\components\auth\JwtAuth;
use humhub\modules\rest\controllers\auth\AuthController;
use humhub\modules\rest\models\ConfigureForm;
use humhub\modules\user\models\User;
use humhub\modules\rest\models\JwtAuthForm;

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
     * @inerhitdoc
     * Do not enforce authentication.
     */
    public $access = ControllerAccess::class;

    /**
     * @inheritdoc
     */
    protected $doNotInterceptActionIds = ['*'];

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors() , [
            'authenticator' => [
                'class' => CompositeAuth::class,
                'authMethods' => ArrayHelper::merge(
                    ConfigureForm::getInstance()->enableJwtAuth ? [[
                        'class' => JwtAuth::class,
                    ]] : [],
                    ConfigureForm::getInstance()->enableBearerAuth ? [[
                        'class' => HttpBearerAuth::class,
                    ]] : [],
                    ConfigureForm::getInstance()->enableBearerAuth && ConfigureForm::getInstance()->enableQueryParamAuth ? [[
                        'class' => QueryParamAuth::class,
                    ]] : [],
                    ConfigureForm::getInstance()->enableBasicAuth ? [[
                        'class' => HttpBasicAuth::class,
                        'auth' => function($username, $password) {
                            return AuthController::authByUserAndPassword($username, $password);
                        },
                    ]] : [],
                ),
            ],
        ]);
    }

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
     * Checks if users is allowed to use the Rest API
     *
     * @param User $user
     * @return bool
     */
    public function isUserEnabled(User $user)
    {
        $config = new JwtAuthForm();
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


    /**
     * Attach files to Content
     *
     * @param Content|null $content
     * @return array
     */
    protected function attachFilesToContent(?Content $content): array
    {
    }
}
