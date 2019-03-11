<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\auth;

use Firebase\JWT\JWT;
use humhub\modules\rest\components\BaseController;
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
        $login = new Login;
        if (! $login->load(Yii::$app->request->post(), '') || ! $login->validate()) {
            return $this->returnError(400, 'Wrong username or password');
        }
        $user = User::findOne(['email' => Yii::$app->request->post('username')]);

        $issuedAt = time();
        $expired = $issuedAt + 3600;
        $data = [
            'iat'  => $issuedAt,
            'jti'  => base64_encode($this->getApiKey()),
            'iss'  => Yii::$app->settings->get('baseUrl'),
            'nbf'  => $issuedAt,
            'exp'  => $expired,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ]
        ];

        $jwt = JWT::encode($data, $this->getApiKey(), 'HS512');

        return $this->returnSuccess('Success', 200, [
            'auth_token' => $jwt,
            'expired_at' => $expired
        ]);
    }
}