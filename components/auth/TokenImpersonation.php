<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\components\auth;

use humhub\modules\user\components\Impersonation;
use humhub\modules\user\models\User as UserModel;

/**
 * The impersonation state of a request authenticated by an impersonate token
 * (`Authorization: Impersonate <token>`, see [[ImpersonateAuth]]).
 *
 * The core detects an impersonation by a session marker, which a session-less API request never
 * has. Here the token itself is the impersonation, so the request counts as impersonated as long
 * as it is authenticated, and the restrictions of the core apply as in the web UI (e.g. private
 * content is hidden when [[allowPrivateContentAccess]] is disabled).
 */
class TokenImpersonation extends Impersonation
{
    /**
     * @inheritdoc
     */
    public function isActive(): bool
    {
        return !$this->user->getIsGuest();
    }

    /**
     * @inheritdoc
     *
     * The impersonating administrator is not stored with the token.
     */
    public function getImpersonator(): ?UserModel
    {
        return null;
    }

    /**
     * @inheritdoc
     *
     * A token impersonation ends with the expiration of the token, there is no session state to restore.
     */
    public function stop(): bool
    {
        return false;
    }
}
