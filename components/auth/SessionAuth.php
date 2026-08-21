<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\components\auth;

use Yii;
use yii\filters\auth\AuthMethod;
use yii\web\ForbiddenHttpException;
use yii\web\Request;
use yii\web\User;

/**
 * Authenticates API requests by the regular HumHub browser session.
 *
 * This method is registered LAST in the CompositeAuth chain (see `BaseController::behaviors()`),
 * so every token method (JWT, Bearer, query param, HTTP Basic, Impersonate) takes precedence:
 * a request that carries a valid token is authenticated as the token user even when a session
 * cookie is present ("token wins"). Only when no token method yields an identity is the browser
 * session consulted. An *invalid* token falls through to session auth — standard CompositeAuth
 * fall-through semantics.
 *
 * Security contract:
 *
 * - State-changing requests (POST/PUT/PATCH/DELETE) additionally require a valid CSRF token —
 *   the `X-CSRF-Token` header (same mechanism `humhub.client` uses, fed from the `csrf-token`
 *   meta tag) or the `_csrf` body parameter. Without it, a session-cookie-authenticated API
 *   would be a CSRF hole. GET/HEAD/OPTIONS are exempt. Token-authenticated requests are never
 *   CSRF-checked (unchanged); a session-authenticated state-changing request with a missing or
 *   invalid CSRF token fails with a 403 JSON response.
 *
 * - Session auth deliberately BYPASSES the "Enabled for all registered users" / user allowlist
 *   gate ({@see \humhub\modules\rest\components\BaseController::isUserEnabled()}). That gate
 *   limits who may access the API *from outside the browser* with self-obtained credentials or
 *   tokens — per its own admin hints it "affects JWT and HTTP Basic Authentication methods
 *   only". A session-authenticated call can do nothing the same user's browser session cannot
 *   already do through the normal web controllers, so gating it would only break the browser
 *   (Vue) UI for non-allowlisted users without any security gain.
 *
 * - Only an established, logged-in session authenticates; guests stay 401. The auto-login
 *   ("remember me") identity cookie alone is NOT accepted (`enableAutoLogin` stays off on the
 *   API user component) — core re-establishes the session on any regular page load before a
 *   browser UI issues API calls.
 *
 * @since 0.13
 */
class SessionAuth extends AuthMethod
{
    /**
     * @inheritdoc
     * @param User $user
     * @param Request $request
     * @throws ForbiddenHttpException when a session user issues a state-changing request
     * without a valid CSRF token
     */
    public function authenticate($user, $request, $response)
    {
        // Cheap opt-out: no session cookie and no already-active session (test environment)
        // means there is nothing to restore — pure token clients never touch the session.
        $session = Yii::$app->session;
        if (!$session->getHasSessionId() && !$session->getIsActive()) {
            return null;
        }

        $identity = $this->getSessionIdentity($user);
        if ($identity === null) {
            return null;
        }

        if (!$this->validateCsrfToken($request)) {
            throw new ForbiddenHttpException('Unable to verify your data submission. Session-authenticated modifying requests require a valid CSRF token (X-CSRF-Token header).');
        }

        return $identity;
    }

    /**
     * Restores the identity from the HumHub browser session.
     *
     * `BaseController::beforeAction()` configures the API user component session-less
     * (`enableSession = false`) so that token logins can never write into the browser session
     * (`yii\web\User::login()` would otherwise regenerate the session id and rebind the session
     * to the token user). Instead of enabling sessions for the whole request, the session
     * identity is restored through a temporary window here: `yii\web\User::getIdentity()`
     * caches the restored identity on the component, so everything after this call — including
     * `Yii::$app->user` access in actions — behaves as usual while the component stays
     * session-less for writes.
     *
     * This runs the full `yii\web\User::renewAuthStatus()` machinery: session auth key
     * validation plus the same `authTimeout` / `absoluteAuthTimeout` expiry rules as the web UI
     * (the timeouts are copied from the application's user component in
     * `BaseController::beforeAction()`).
     */
    private function getSessionIdentity(User $user)
    {
        $enableSession = $user->enableSession;
        $user->enableSession = true;
        try {
            return $user->getIdentity();
        } finally {
            $user->enableSession = $enableSession;
        }
    }

    /**
     * Validates the CSRF token for state-changing requests; safe methods (GET/HEAD/OPTIONS)
     * always pass — see `yii\web\Request::validateCsrfToken()`.
     *
     * `BaseController::beforeAction()` disables the CSRF cookie so API responses never emit
     * one, but the browser's true CSRF token lives in the `_csrf` cookie (HumHub core default),
     * so cookie lookup must be re-enabled while validating.
     */
    private function validateCsrfToken(Request $request): bool
    {
        $enableCsrfCookie = $request->enableCsrfCookie;
        $request->enableCsrfCookie = true;
        try {
            return $request->validateCsrfToken();
        } finally {
            $request->enableCsrfCookie = $enableCsrfCookie;
        }
    }
}
