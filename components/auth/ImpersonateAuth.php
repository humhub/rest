<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2023 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\components\auth;

use Yii;
use yii\db\Expression;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\StringHelper;
use humhub\modules\rest\models\ImpersonateAuthToken;
use humhub\modules\user\models\User;
use Firebase\JWT\JWT;

class ImpersonateAuth extends HttpBearerAuth
{
    public $pattern = '/^Impersonate\s+(.*?)$/';

    public function authenticate($user, $request, $response)
    {
        $authHeader = $request->getHeaders()->get($this->header);

        if ($authHeader !== null) {
            if ($this->pattern !== null) {
                if (preg_match($this->pattern, $authHeader, $matches)) {
                    $authHeader = $matches[1];
                } else {
                    return null;
                }

                if (!StringHelper::startsWith($authHeader, 'impersonated-')) {
                    return null;
                }
            }

            $accessToken = ImpersonateAuthToken::find()
                ->where(['token' => $authHeader])
                ->andWhere(['>', 'expiration', new Expression('NOW()')])
                ->one();

            if ($accessToken && ($identity = $accessToken->user)) {
                $user->login($identity);
                Yii::$app->user->isImpersonated = true;
            } else {
                $identity = null;
            }

            if ($identity === null) {
                $this->challenge($response);
                $this->handleFailure($response);
            }

            return $identity;
        }

        return null;
    }
}
