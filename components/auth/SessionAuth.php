<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\components\auth;

use humhub\components\gates\RequestClass;
use humhub\modules\user\components\Impersonation;
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
 * - A session-authenticated request is made gate-visible: the same user gates a browser
 *   session is subject to (2FA and other non-API gates) are enforced here, because the
 *   session-less API user component would otherwise make core's `GateFilter` misclassify the
 *   request as an API request and skip them (a 2FA-pending user must not reach the API).
 *   See {@see enforceOpenGates()} and `docs/vue-session-api.md` §4.4.
 *
 * - An active admin impersonation is bound to the browser session and cannot use the API on
 *   this branch: it is detected from the session marker and rejected (fail closed), because
 *   the session-less API user component prevents core 1.19's impersonation private-content
 *   restriction from applying. See {@see isImpersonationSession()} and §4.5 of that document.
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

        // I3: an admin impersonation is bound to the browser session. Because the API user
        // component is session-less, core's `Impersonation::isActive()` cannot detect it and
        // its 1.19 private-content restriction would silently not apply — so a session-bound
        // impersonation is rejected (fail closed) until the core-side explicit-session signal
        // lands (see docs/vue-session-api.md §4.5).
        if ($this->isImpersonationSession()) {
            throw new ForbiddenHttpException('Impersonation is not supported over the API. Stop the impersonation to continue.');
        }

        // C2: enforce the user gates a browser session must pass (2FA and other non-API gates).
        $this->enforceOpenGates();

        return $identity;
    }

    /**
     * Whether the current browser session is an active admin impersonation.
     *
     * The session identity has just been restored, so the session is open and the marker can
     * be read directly. Core's {@see Impersonation::isActive()} cannot be used here: it
     * short-circuits `false` while `enableSession` is off, which the API user component pins.
     */
    private function isImpersonationSession(): bool
    {
        return Yii::$app->has('session') && Yii::$app->session->has(Impersonation::SESSION_KEY);
    }

    /**
     * Enforces the user gates a browser session is subject to on a session-authenticated
     * request.
     *
     * Core's `GateFilter::getRequestClass()` infers {@see RequestClass::Api} purely from
     * `Yii::$app->user->enableSession === false`, which `BaseController` pins for every REST
     * request. A cookie-authenticated request is therefore misclassified as an API request
     * and skips every gate that does not apply to API requests (2FA, legal, onboarding, …) —
     * so a user who passed only the first factor could call every endpoint.
     *
     * This re-classifies the request the way `GateFilter` would for a real browser session
     * (never Api) and rejects it when a gate is open. Gates that also apply to
     * {@see RequestClass::Api} (e.g. must-change-password, maintenance mode) are already
     * enforced by the core `GateFilter` on this same request, so only the misclassification
     * gap is closed here — they are not applied twice.
     *
     * @throws ForbiddenHttpException when an open gate intercepts the request
     */
    private function enforceOpenGates(): void
    {
        if (!Yii::$app->has('gateManager')) {
            return;
        }

        $request = Yii::$app->request;
        $requestClass = ($request->getIsAjax() || $request->getIsPjax())
            ? RequestClass::Ajax
            : RequestClass::FullPage;

        $gate = Yii::$app->gateManager->findOpenGate($requestClass, (string)Yii::$app->requestedRoute);

        if ($gate !== null && !$gate->appliesTo(RequestClass::Api)) {
            throw new ForbiddenHttpException('This action requires completing the "' . $gate->getId() . '" step first.');
        }
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
     * Validates the CSRF token for state-changing requests without ever minting one; safe
     * methods (GET/HEAD/OPTIONS) always pass.
     *
     * `yii\web\Request::validateCsrfToken()` is deliberately NOT used: it calls
     * `getCsrfToken()`, which — with the CSRF cookie enabled — generates a fresh token and
     * emits a `_csrf` Set-Cookie whenever the request carries none, clobbering the page's real
     * token (M5). Instead the browser's true token is read straight from the `_csrf` cookie
     * (HumHub core default) and compared timing-safely against the client-supplied token; no
     * cookie means no valid token. No API response ever sets a cookie this way.
     */
    private function validateCsrfToken(Request $request): bool
    {
        if (in_array($request->getMethod(), $request->csrfTokenSafeMethods, true)) {
            return true;
        }

        // The `_csrf` cookie holds the raw token; missing cookie ⇒ no valid token.
        $trueToken = $request->getCookies()->getValue($request->csrfParam);
        if (!is_string($trueToken) || $trueToken === '') {
            return false;
        }

        // The client sends the masked token via the X-CSRF-Token header (or the `_csrf` body
        // param), exactly like `humhub.client` does in the browser.
        $clientToken = $request->getCsrfTokenFromHeader() ?? $request->getBodyParam($request->csrfParam);
        if (!is_string($clientToken) || $clientToken === '') {
            return false;
        }

        $security = Yii::$app->security;

        // `unmaskToken()` recovers the raw token from the masked client value for a timing-safe
        // comparison — no token generation, no Set-Cookie.
        return $security->compareString($security->unmaskToken($clientToken), $trueToken);
    }
}
