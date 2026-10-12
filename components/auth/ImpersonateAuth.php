<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2023 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\components\auth;

use humhub\modules\rest\models\ImpersonateAuthToken;
use yii\db\Expression;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\StringHelper;

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
                // Note: up to HumHub 1.18 this additionally set `Yii::$app->user->isImpersonated`,
                // a flag of the `Impersonator` user-component behavior. HumHub 1.19 replaced that
                // behavior with the session-bound `Impersonation` component
                // (`Yii::$app->user->impersonation`, core #8372), so the flag no longer exists —
                // and the API user component is deliberately session-less, so there is no session
                // state to mark either. Applying 1.19's impersonation restrictions (hidden private
                // content) to impersonate-token requests is a separate follow-up.
                $user->login($identity);
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
