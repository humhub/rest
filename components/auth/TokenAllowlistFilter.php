<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\components\auth;

use humhub\modules\oauth\Module as OAuthModule;
use Yii;
use yii\base\ActionFilter;

/**
 * Applies the module's user allowlist ({@see AuthMethods::isUserEnabled()}) to requests
 * authenticated by core's OAuth tokens. Other requests pass: the module's own methods check
 * it themselves.
 *
 * @since 0.13
 */
class TokenAllowlistFilter extends ActionFilter
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (OAuthModule::instance()->activeGrant === null) {
            return true;
        }

        $user = Yii::$app->user->getIdentity();
        if ($user !== null && AuthMethods::isUserEnabled($user)) {
            return true;
        }

        $response = Yii::$app->response;
        $response->setStatusCode(403);
        $response->data = ['code' => 403, 'message' => 'API access is not enabled for this user.'];

        return false;
    }
}
