<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2023 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\components\auth;

use yii\filters\auth\HttpBearerAuth;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use humhub\modules\rest\models\JwtAuthForm;
use humhub\modules\user\models\User;

class JwtAuth extends HttpBearerAuth
{
    public function authenticate($user, $request, $response)
    {
        $authHeader = $request->getHeaders()->get($this->header);

        if ($request->getHeaders()->get($this->header) && $this->pattern !== null && preg_match($this->pattern, $authHeader, $matches)) {
            $token = $matches[1];

            try {
                $validData = JWT::decode($token, new Key(JwtAuthForm::getInstance()->jwtKey, 'HS512'));
                if (
                    !empty($validData->uid)
                    && ($identity = User::find()->active()->andWhere(['user.id' => $validData->uid])->one())
                    && $user->login($identity)
                ) {
                    return $identity;
                }
            } catch (Exception $e) {
                return null;
            }
        }

        return null;
    }
}
