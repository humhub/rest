<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\components;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\content\models\Content;
use humhub\modules\rest\components\auth\AuthMethods;
use humhub\modules\rest\components\behaviors\LanguagePickerBehavior;
use humhub\modules\rest\components\User as UserComponent;
use humhub\modules\rest\Module;
use humhub\modules\user\models\User;
use Yii;
use yii\data\Pagination;
use yii\db\ActiveQuery;
use yii\filters\auth\CompositeAuth;
use yii\helpers\ArrayHelper;
use yii\web\JsonParser;
use yii\web\NotFoundHttpException;

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

    public function behaviors()
    {
        return ArrayHelper::merge([
            'authenticator' => [
                'class' => CompositeAuth::class,
                // The same methods this module contributes to core's API controllers, see
                // {@see AuthMethods}. `/api/v1` is token-only: browser-session
                // authentication is a core opt-in, per controller.
                'authMethods' => AuthMethods::collect(),
            ],
            'languagePicker' => [
                'class' => LanguagePickerBehavior::class,
            ],
        ], parent::behaviors());
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        // Defence in depth: hard-fail any request that reached a REST controller off the API
        // URL rules — i.e. whose path is not under the API prefix (a bare `/rest/<controller>/
        // <action>` URL). Together with the `rest/<tmpParam>` catch-all in
        // Events::onBeforeRequest() this guarantees a mutating action can never be executed
        // off-rule as an unconstrained, CSRF-exempt plain request. Must run before auth.
        if (!str_starts_with(Yii::$app->request->pathInfo, Module::API_URL_PREFIX)) {
            Yii::$app->response->format = 'json';
            throw new NotFoundHttpException();
        }

        Yii::$app->set('user', [
            'class' => UserComponent::class,
            'identityClass' => User::class,
            // Always session-less: token logins (`yii\web\User::login()`) must never write
            // into the browser session.
            'enableSession' => false,
        ]);

        Yii::$app->response->format = 'json';

        Yii::$app->request->setBodyParams(null);
        Yii::$app->request->enableCsrfCookie = false;
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
        return AuthMethods::isUserEnabled($user);
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
