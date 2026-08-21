<?php

namespace rest\api;

use Codeception\Util\HttpCode;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;
use Yii;

/**
 * Tests for browser session authentication (SessionAuth), see
 * `components/auth/SessionAuth.php` for the security contract.
 */
class SessionAuthCest extends HumHubApiTestCest
{
    /**
     * @var int fixture id of User1 (core user fixture)
     */
    private const USER1_ID = 2;

    /**
     * @var string bearer access token of User1 (BearerAccessTokenFixture)
     */
    private const USER1_BEARER_TOKEN = '_sB714dci3pUh6FZw5BFA0wB2ri5TfQ-dxs32iaK920BI1eHn7SX0UphARYr4J-duJbF-ZuULdjOuqc1DSH3DB';

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

        Yii::$app->getModule('rest')->settings->set('enableSessionAuth', false);

        $I->amLoggedInAs(self::USER1_ID);
        $I->sendGet('auth/current');
        $I->seeCodeResponseContainsJson(HttpCode::UNAUTHORIZED, ['message' => 'Your request was made with invalid credentials.']);

        $I->amBearerAuthenticated(self::USER1_BEARER_TOKEN);
        $I->sendGet('auth/current');
        $I->seeSuccessResponseContainsJson($I->getUserDefinition('User1'));
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
}
