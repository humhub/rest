<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2023 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\components;

use yii\db\Expression;
use yii\web\IdentityInterface;
use humhub\modules\rest\models\RestUserBearerToken;

class User extends \humhub\modules\user\components\User
{
    public function loginByAccessToken($token, $type = null)
    {
        /* @var $class IdentityInterface */
        $class = $this->identityClass;

        $accessToken = RestUserBearerToken::find()
            ->where(['token' => $token])
            ->andWhere(['>', 'expiration', new Expression('NOW()')])
            ->one();

        if ($accessToken && $accessToken->user && $this->login($accessToken->user)) {
            return $accessToken->user;
        }

        return null;
    }
}
