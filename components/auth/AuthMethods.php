<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\components\auth;

use humhub\modules\rest\controllers\auth\AuthController;
use humhub\modules\rest\models\ConfigureForm;
use humhub\modules\user\models\User;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\helpers\ArrayHelper;

/**
 * The machine authentication methods this module provides, in one place.
 *
 * Two consumers:
 *
 * - this module's own controllers ({@see \humhub\modules\rest\components\BaseController}),
 *   serving `/api/v1`,
 * - every API controller of the platform, through the core collect event (see
 *   {@see \humhub\modules\rest\Events::onCollectApiAuthMethods()}). An installation with
 *   this module can therefore call the core endpoints with a token too; a core-only
 *   installation has browser-session authentication and nothing else.
 *
 * Browser-session authentication is deliberately NOT part of this list: it lives in core
 * ({@see \humhub\components\api\SessionAuth}) and each API controller opts in to it
 * individually. See `docs/api-stack.md` and core's `docs/develop/concept-api.md`.
 *
 * @since 0.13
 */
class AuthMethods
{
    /**
     * Method configurations for {@see \yii\filters\auth\CompositeAuth::$authMethods},
     * according to the module's current settings.
     *
     * Order matters only among themselves (first match wins, `null` falls through to the
     * next); core appends session authentication after all of them.
     */
    public static function collect(): array
    {
        $config = ConfigureForm::getInstance();

        return ArrayHelper::merge(
            $config->enableJwtAuth ? [[
                'class' => JwtAuth::class,
            ]] : [],
            $config->enableBearerAuth ? [[
                'class' => HttpBearerAuth::class,
            ]] : [],
            $config->enableBearerAuth && $config->enableQueryParamAuth ? [[
                'class' => QueryParamAuth::class,
            ]] : [],
            $config->enableBasicAuth ? [[
                'class' => HttpBasicAuth::class,
                'auth' => function ($username, $password) {
                    if (($identity = AuthController::authByUserAndPassword($username, $password)) && static::isUserEnabled($identity)) {
                        return $identity;
                    }

                    return null;
                },
            ]] : [],
            [[
                'class' => ImpersonateAuth::class,
            ]],
        );
    }

    /**
     * Whether the given user may use the API at all - the "Enabled for all registered users"
     * setting and the user allowlist below it, which per its own admin hint applies to the
     * JWT and HTTP Basic methods only.
     */
    public static function isUserEnabled(User $user): bool
    {
        $config = ConfigureForm::getInstance();

        if (!empty($config->enabledForAllUsers)) {
            return true;
        }

        return in_array($user->guid, (array)$config->enabledUsers);
    }
}
