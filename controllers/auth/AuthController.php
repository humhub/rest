<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\auth;

use Firebase\JWT\JWT;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\UserDefinitions;
use humhub\modules\rest\models\JwtAuthForm;
use humhub\modules\user\models\forms\Login;
use humhub\modules\user\models\User;
use humhub\modules\user\services\AuthClientService;
use Yii;
use yii\web\JsonParser;

class AuthController extends BaseController
{
    public function beforeAction($action)
    {
        if ($action->id == 'current') {
            return parent::beforeAction($action);
        }

        Yii::$app->response->format = 'json';
        Yii::$app->request->setBodyParams(null);
        Yii::$app->request->parsers['application/json'] = JsonParser::class;

        return true;
    }

    public function actionIndex()
    {
        $user = static::authByUserAndPassword(Yii::$app->request->post('username'), Yii::$app->request->post('password'));

        if ($user === null) {
            return $this->returnError(400, 'Wrong username or password');
        }

        if (!$this->isUserEnabled($user)) {
            return $this->returnError(401, 'Invalid user!');
        }

        $issuedAt = time();
        $data = [
            'iat' => $issuedAt,
            'iss' => Yii::$app->settings->get('baseUrl'),
            'nbf' => $issuedAt,
            'uid' => $user->id,
            'email' => $user->email
        ];

        $config = JwtAuthForm::getInstance();
        if (!empty($config->jwtExpire)) {
            $data['exp'] = $issuedAt + (int)$config->jwtExpire;
        }

        $jwt = JWT::encode($data, $config->jwtKey, 'HS512');

        return $this->returnSuccess('Success', 200, [
            'auth_token' => $jwt,
            'expired_at' => (!isset($data['exp'])) ? 0 : $data['exp']
        ]);
    }


    public static function authByUserAndPassword($username, $password)
    {
        $login = new Login;
        if (!$login->load(['username' => $username, 'password' => $password], '') || !$login->validate()) {
            return null;
        }

        $user = (new AuthClientService($login->authClient))->getUser();
        return $user;
    }

    /**
     * Get current User details
     *
     * @return array
     */
    public function actionCurrent()
    {
        $user = User::findOne(['id' => Yii::$app->user->id]);
        if ($user === null) {
            return $this->returnError(404, 'User not found!');
        }

        return UserDefinitions::getUser($user);
    }
}