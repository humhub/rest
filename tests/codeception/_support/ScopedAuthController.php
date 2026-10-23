<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace rest;

use humhub\modules\rest\controllers\auth\AuthController;

/**
 * `GET auth/current` with a declared scope — the module declares none yet, see OAuthTokenCest.
 * `POST auth/impersonate` (inherited) stays undeclared.
 */
class ScopedAuthController extends AuthController
{
    protected array $scopes = ['current' => 'users:read'];
}
