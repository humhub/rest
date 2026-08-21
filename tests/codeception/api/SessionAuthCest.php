<?php

namespace rest\api;

use Codeception\Util\HttpCode;
use humhub\components\gates\GateInitEvent;
use humhub\components\gates\GateManager;
use humhub\components\gates\UserGate;
use humhub\modules\comment\models\Comment;
use humhub\modules\user\components\Impersonation;
use PHPUnit\Framework\Assert;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;
use Yii;
use yii\base\Event;

/**
 * Tests for browser session authentication (SessionAuth), see
 * `components/auth/SessionAuth.php` for the security contract.
 */
class SessionAuthCest extends HumHubApiTestCest
{
    /**
     * @var int fixture id of Admin (core user fixture)
     */
    private const ADMIN_ID = 1;

    /**
     * @var int fixture id of User1 (core user fixture)
     */
    private const USER1_ID = 2;

    /**
     * @var string bearer access token of User1 (BearerAccessTokenFixture)
     */
    private const USER1_BEARER_TOKEN = '_sB714dci3pUh6FZw5BFA0wB2ri5TfQ-dxs32iaK920BI1eHn7SX0UphARYr4J-duJbF-ZuULdjOuqc1DSH3DB';

    /**
     * Registers an always-open gate that applies to browser-session (non-API) requests only —
     * the shape of the 2FA gate — for {@see testSessionAuthEnforcesOpenGate()}.
     */
    public static function _registerOpenSessionGate(GateInitEvent $event): void
    {
        $event->manager->register(new class extends UserGate {
            public function getId(): string
            {
                return 'rest-test-gate';
            }

            public function getSortOrder(): int
            {
                return self::SORT_SECOND_FACTOR;
            }

            public function isOpen(): bool
            {
                return true;
            }

            public function getRoute(): array
            {
                return ['/user/auth/logout'];
            }

            public function isCacheable(): bool
            {
                return false;
            }
        });
    }

    public function _before()
    {
        parent::_before();
        // Session auth defaults OFF (see ConfigureForm); enable it for these tests.
        Yii::$app->getModule('rest')->settings->set('enableSessionAuth', true);
    }

    public function _after()
    {
        // Restore the default so the setting does not leak into other cests on the shared DB.
        Yii::$app->getModule('rest')->settings->set('enableSessionAuth', false);
    }

    public function testSessionAuthenticatedGet(ApiTester $I)
    {
        $I->wantTo('authenticate a GET request by browser session');

        $I->amLoggedInAs(self::USER1_ID);

        $I->sendGet('auth/current');
        $I->seeSuccessResponseContainsJson($I->getUserDefinition('User1'));
    }

    public function testGuestIsRejected(ApiTester $I)
    {
        $I->wantTo('be rejected as guest without token or session');

        $I->sendGet('auth/current');
        $I->seeCodeResponseContainsJson(HttpCode::UNAUTHORIZED, ['message' => 'Your request was made with invalid credentials.']);
    }

    public function testSessionModifyingRequestWithoutCsrfIsRejected(ApiTester $I)
    {
        $I->wantTo('see a session-authenticated modifying request rejected without CSRF token');

        $I->amLoggedInAs(self::USER1_ID);

        $I->sendPatch('notification/mark-as-seen');
        $I->seeCodeResponseContainsJson(HttpCode::FORBIDDEN, [
            'message' => 'Unable to verify your data submission. Session-authenticated modifying requests require a valid CSRF token (X-CSRF-Token header).',
        ]);
    }

    public function testSessionModifyingRequestWithCsrfSucceeds(ApiTester $I)
    {
        $I->wantTo('perform a session-authenticated modifying request with a valid CSRF token');

        $I->amLoggedInAs(self::USER1_ID);

        // The raw CSRF token lives in the `_csrf` cookie; the client sends the masked
        // form in the X-CSRF-Token header — same mechanism as humhub.client in the browser.
        $rawToken = Yii::$app->security->generateRandomString();
        $I->setCookie('_csrf', $rawToken);
        $I->haveHttpHeader('X-CSRF-Token', Yii::$app->security->maskToken($rawToken));

        $I->sendPatch('notification/mark-as-seen');
        $I->seeSuccessMessage('All notifications successfully marked as seen');
    }

    public function testSessionAuthDisabledSetting(ApiTester $I)
    {
        $I->wantTo('see session auth rejected when disabled while token auth still works');

        $settings = Yii::$app->getModule('rest')->settings;
        $settings->set('enableSessionAuth', false);

        try {
            $I->amLoggedInAs(self::USER1_ID);
            $I->sendGet('auth/current');
            $I->seeCodeResponseContainsJson(HttpCode::UNAUTHORIZED, ['message' => 'Your request was made with invalid credentials.']);

            $I->amBearerAuthenticated(self::USER1_BEARER_TOKEN);
            $I->sendGet('auth/current');
            $I->seeSuccessResponseContainsJson($I->getUserDefinition('User1'));
        } finally {
            // _before() enabled session auth for this cest; restore that state for later tests.
            $settings->set('enableSessionAuth', true);
        }
    }

    public function testTokenWinsOverSession(ApiTester $I)
    {
        $I->wantTo('see token auth take precedence over an existing browser session');

        // Session of User1, bearer token of... also User1 — use basic auth of Admin instead
        // to observe precedence via the returned identity.
        $I->amLoggedInAs(self::USER1_ID);
        $I->amHttpAuthenticated('Admin', 'admin&humhub@PASS%worD!');

        $I->sendGet('auth/current');
        $I->seeSuccessResponseContainsJson($I->getUserDefinition('Admin'));
    }

    public function testOffRuleMutationIsBlocked(ApiTester $I)
    {
        $I->wantTo('see a bare /rest/... URL blocked instead of executing a mutating action (C1)');

        // Session of an admin who is allowed to delete fixture comment 1.
        $I->amLoggedInAs(self::ADMIN_ID);

        // A bare, unprefixed /rest/<controller>/<action> URL must never resolve to a controller
        // action. Before the fix this executed WindowController::actionDelete as a plain GET —
        // no verb constraint, no CSRF check (a SameSite=Lax cross-site top-level GET CSRF hole).
        $I->sendGet('http://localhost:8080/rest/comment/window/delete?id=1');
        $I->seeResponseCodeIs(404);

        // The targeted comment must still exist — the delete never ran.
        Assert::assertNotNull(Comment::findOne(['id' => 1]), 'Off-rule GET must not have deleted the comment');
    }

    public function testCsrfValidationNeverMintsCookie(ApiTester $I)
    {
        $I->wantTo('see CSRF validation reject without ever Set-Cookie-ing a fresh _csrf token (M5)');

        $I->amLoggedInAs(self::USER1_ID);

        // Modifying request without a _csrf cookie or token: rejected 403, and the response must
        // NOT mint a _csrf Set-Cookie (which would clobber the browser page's real token).
        $I->sendPatch('notification/mark-as-seen');
        $I->seeResponseCodeIs(403);

        foreach ((array)$I->grabHttpHeader('Set-Cookie', false) as $setCookie) {
            Assert::assertStringNotContainsString('_csrf', (string)$setCookie, 'API response must not Set-Cookie a _csrf token');
        }
    }

    public function testSessionAuthEnforcesOpenGate(ApiTester $I)
    {
        $I->wantTo('see an open non-API gate (e.g. a pending 2FA check) reject a session request (C2)');

        Event::on(GateManager::class, GateManager::EVENT_INIT_GATES, [self::class, '_registerOpenSessionGate']);
        try {
            $I->amLoggedInAs(self::USER1_ID);

            // Without the gate-visibility fix the session-less API user component makes core's
            // GateFilter classify this as an API request and skip the gate → 200. It must be 403.
            $I->sendGet('auth/current');
            $I->seeResponseCodeIs(403);
        } finally {
            Event::off(GateManager::class, GateManager::EVENT_INIT_GATES, [self::class, '_registerOpenSessionGate']);
        }
    }

    public function testSessionImpersonationIsRejected(ApiTester $I)
    {
        $I->wantTo('see a session-bound admin impersonation rejected on the API (I3, fail closed)');

        $I->amLoggedInAs(self::USER1_ID);

        // Simulate an active impersonation: core stores the impersonator id under this session
        // key (Impersonation::SESSION_KEY). The session-less API user component would otherwise
        // hide the impersonation and lift core 1.19's private-content restriction.
        Yii::$app->session->set(Impersonation::SESSION_KEY, ['id' => self::ADMIN_ID, 'duration' => 0]);
        try {
            $I->sendGet('auth/current');
            $I->seeResponseCodeIs(403);
        } finally {
            Yii::$app->session->remove(Impersonation::SESSION_KEY);
        }
    }
}
