<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\auth;

use Firebase\JWT\JWT;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\models\ConfigureForm;
use humhub\modules\user\authclient\AuthClientHelpers;
use humhub\modules\user\models\forms\Login;
use humhub\modules\user\models\User;
use Yii;

class AuthController extends BaseController
{
    public function beforeAction($action)
    {
        return true;
    }

    public function actionIndex()
    {
        $user = static::authByUserAndPassword(Yii::$app->request->post('username'), Yii::$app->request->post('password'));

        if ($user === null) {
            return $this->returnError(400, 'Wrong username or password');
        }

        $issuedAt = time();
        $data = [
            'iat' => $issuedAt,
            'iss' => Yii::$app->settings->get('baseUrl'),
            'nbf' => $issuedAt,
            'uid' => $user->id,
            'email' => $user->email
        ];

        $config = ConfigureForm::getInstance();
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

        $user = AuthClientHelpers::getUserByAuthClient($login->authClient);
        return $user;
    }
}